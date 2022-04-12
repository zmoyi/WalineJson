<?php


    use Typecho\Common;
    use Typecho\Date;
    use Typecho\Db;
    use Typecho\Db\Exception;
    use Typecho\Widget;

    use Typecho\Widget\Request;
    use Typecho\Widget\Response;
    use Widget\ActionInterface;
    use Widget\Base\Comments;
    use Widget\Options;

    include_once 'user_agent.php';



    class walineJson_Action extends Widget implements ActionInterface
    {
        private  $db;

        private  $comments;

        private $agent;


        public function __construct(Request $request, Response $response, $params = null)
        {
            $this->db = Db::get();
            $this->comments = new Comments($request,$response);

            parent::__construct($request, $response, $params);
        }

        /**
         * @inheritDoc
         */
        public function action()
        {

            // TODO: Implement action() method.
        }
        /**
         * 评论获取
         */
        public function comment()
        {


            if ($this->request->isPost()){

                $data = file_get_contents("php://input");
                $data = json_decode($data, true);
                $this->insert_comments($data);
            }
            else if ($this->request->isGet()){
                $data = $this->request->from('type','url','path','page','pageSize');
                /**
                 * 获取评论数
                 */
                $this->getCommentNum($data['type'],$data['url']);
                /**
                 * 返回评论
                 */
                $data = $this->request->from('type','url','path','page','pageSize');
                $this->getComments($data['path'],$data['page'],$data['pageSize']);
            }

        }

        /**
         * 获取评论数
         *
         */
        public function getCommentNum($type,$size)
        {
            if ($type=='count'&&!empty($size)){
                $nums = $this->db->select()->from('table.contents')->where('cid = ?',$size);
                $num = $this->comments->size($nums);
                $this->response->throwContent($num);
            }

        }

        /**
         * 获取所有评论
         *
         */
        public function getComments($path,$page,$pageSize)
        {
            $select = $this->comments->select();
            $res =$select->order('table.comments.coid', Db::SORT_DESC)
                ->where('cid =?',$path)
                ->page($page, $pageSize);
            $res = $this->db->fetchAll($res);
            $data =[];
            $children =[];
            foreach ($res as $key =>  $value){
                if ($value['status']=='approved'){
                    $this->agent = new CI_User_agent($value['agent']);
                    $text = $this->comments->autoP($this->comments->markdown($value['text']));
                    if ($value['parent']==0){
                        $datas = [
                            'avatar'=>$this->is_avatar($value['mail']),
                            'browser' => $this->agent->browser(),
                            'comment' => $text,
                            'insertedAt' => date("Y/m/d H:i:s",$value['created']),
                            'link' => $value['url'],
                            'nick' => $value['author'],
                            'objectId' => $value['coid'],
                            'os' => $this->agent->platform(),
                            'pid'=>$value['parent'],
                            'rid'=>$value['rid'],
                            'status'=>$value['status'],
                            'sticky'=>null,
                            'children'=>$children
                        ];
                        if ($value['type']=='administrator')
                            $datas['type'] = $value['type'];
                        array_push($data,$datas);
                    }else{
                        $childrens= [
                            'avatar'=>$this->is_avatar($value['mail']),
                            'browser' => $this->agent->browser(),
                            'comment' => $text,
                            'insertedAt' => date("Y/m/d H:i:s",$value['created']),
                            'link' => $value['url'],
                            'nick' => $value['author'],
                            'objectId' => $value['coid'],
                            'os' => $this->agent->platform(),
                            'pid'=>$value['parent'],
                            'rid'=>$value['rid'],
                            'status'=>$value['status'],
                        ];
                        if ($value['type']=='administrator')
                            $childrens['type'] = $value['type'];
                        array_push($children,$childrens);
                    }
                }

            }
            $nums = $this->db->select()->from('table.contents')->where('cid = ?',$path);
            $num = $this->comments->size($nums);
            $to = ceil($num/$pageSize);
            if ($to<=0)
                $to = 1;
           $this->response->throwJson([
               'count'=>$num,
               'page' => $page,
               'pageSize'=>$pageSize,
               'totalPages' =>$to,
               'data' => $data
           ]);
        }
        /**
         *
         * 头像判断
         */
        public function is_avatar($data): string
        {
            $numExp = '/[1-9]([0-9]{5,11})/';
            if (preg_match($numExp,$data))
                return 'https://q1.qlogo.cn/g?b=qq&nk='.$data.'&s=100';
            if ($this->is_Email($data))
                return 'https://q1.qlogo.cn/g?b=qq&nk='.str_replace('@qq.com', '',$data).'&s=100';
            return 'https://seccdn.libravatar.org/avatar/'.md5($data).'?d=retro';
        }

        /**
         *
         * 判断是否为QQ邮箱
         */
        public function is_Email($email): bool
        {
            $reg='/^([0-9]{5,11})@qq.com$/';
            if(preg_match($reg,$email))
                return true;
            return false;
        }

        /**
         *
         * 插入评论
         */
        public function insert_comments($data)
        {
            $plu = Options::alloc()->plugin('walineJson');

            $insertStruct = [
                'cid'      => $data['url'],
                'author'   => $data['nick'],
                'mail'     => $data['mail'],
                'url'      => $data['link'],
                'agent'    => $data['ua'],
                'text'     => $data['comment']
            ];
            if (!empty($data['pid'])&&!empty($data['rid'])&&!empty($data['at']))
            {
                $insertStruct['parent'] = $data['pid'];
                $insertStruct['rid'] = $data['rid'];
                $insertStruct['text'] = '[@'.$data['at'].'](#'.$data['pid'].') '.$data['comment'];
            }
            if ($plu->mail==$data['mail']){
                $insertStruct['author'] =  $plu->name;
                $insertStruct['type'] = 'administrator';
            }
            $sen = $plu->sensitive;
            $stu = $plu->status;
            $array = explode(" ",$sen);
            if ($this->sensitive($array,$data['comment'])=='waiting'||$stu=='waiting')
                $insertStruct['status'] = 'waiting';

            $status = $this->insert($insertStruct);


            $res = $this->db->fetchAll($this->db->select()
                ->from('table.comments')
                ->where('coid =?',$status)
                ->limit(1));
            $res = $res[0];


            $text = $this->comments->autoP($this->comments->markdown($res['text']));
            $this->agent = new CI_User_agent($res['agent']);
            $data = [
                'errno' => 0,
                'errmsg' => '',
                'data'  =>[
                    'link'=>$res['url'],
                    'mail'=>$res['mail'],
                    'nick' => $res['author'],
                    'url' => $res['cid'],
                    'comment' =>$text,
                    'ip' => $res['ip'],
                    'insertedAt'=>date("Y/m/d H:i:s",$res['created']),
                    'status' => $res['status'],
                    'objectId' => $res['coid'],
                    'browser' => $this->agent->browser(),
                    'os' => $this->agent->platform(),
                    'pid' => $res['parent'],
                    'rid' => $res['rid'],
                    'type' => $res['type'],
                    'avatar' => $this->is_avatar($res['mail']),
                    'orig' => $res['text']
                ],
            ];


            $this->response->throwJson($data);

        }


        /**
         * 增加评论
         *
         * @param array $rows 评论结构数组
         * @return integer
         * @throws Exception
         */
        public function insert(array $rows): int
        {
            /** 构建插入结构 */
            $insertStruct = [
                'cid'      => $rows['cid'],
                'created'  => empty($rows['created']) ? Date::time() : $rows['created'],
                'author'   => !isset($rows['author']) || strlen($rows['author']) === 0 ? null : $rows['author'],
                'authorId' => empty($rows['authorId']) ? 0 : $rows['authorId'],
                'ownerId'  => empty($rows['ownerId']) ? 0 : $rows['ownerId'],
                'mail'     => !isset($rows['mail']) || strlen($rows['mail']) === 0 ? null : $rows['mail'],
                'url'      => !isset($rows['url']) || strlen($rows['url']) === 0 ? null : $rows['url'],
                'ip'       => !isset($rows['ip']) || strlen($rows['ip']) === 0 ? $this->request->getIp() : $rows['ip'],
                'agent'    => !isset($rows['agent']) || strlen($rows['agent']) === 0
                    ? $this->request->getAgent() : $rows['agent'],
                'text'     => !isset($rows['text']) || strlen($rows['text']) === 0 ? null : $rows['text'],
                'type'     => empty($rows['type']) ? 'comment' : $rows['type'],
                'status'   => empty($rows['status']) ? 'approved' : $rows['status'],
                'parent'   => empty($rows['parent']) ? 0 : $rows['parent'],
                'rid'      => empty($rows['rid']) ? null : $rows['rid']
            ];

            if (!empty($rows['coid'])) {
                $insertStruct['coid'] = $rows['coid'];
            }

            /** 过长的客户端字符串要截断 */
            if (Common::strLen($insertStruct['agent']) > 511) {
                $insertStruct['agent'] = Common::subStr($insertStruct['agent'], 0, 511, '');
            }

            /** 首先插入部分数据 */
            $insertId = $this->db->query($this->db->insert('table.comments')->rows($insertStruct));

            /** 更新评论数 */
            $num = $this->db->fetchObject($this->db->select(['COUNT(coid)' => 'num'])->from('table.comments')
                ->where('status = ? AND cid = ?', 'approved', $rows['cid']))->num;

            $this->db->query($this->db->update('table.contents')->rows(['commentsNum' => $num])
                ->where('cid = ?', $rows['cid']));

            return $insertId;
        }
        /**
         * @param  array  $list    定义敏感词一维数组
         * @param string  $string  要过滤的内容
         *
                  * @return string $log 处理结果
         *@todo 敏感词过滤，返回结果
         */
        function sensitive(array $list, string $string): string
        {
            $count = 0; //违规词的个数
            $pattern = "/".implode("|",$list)."/i"; //定义正则表达式
            if(preg_match_all($pattern, $string, $matches)){ //匹配到了结果
                $patternList = $matches[0];  //匹配到的数组
                $count = count($patternList);
            }
            if($count==0){
                $log = 'approved';
            }else{
                $log = 'waiting';
            }
            return $log;
        }




    }