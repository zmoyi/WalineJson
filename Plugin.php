<?php

namespace TypechoPlugin\walineJson;

use Typecho\Db;
use Typecho\Plugin\Exception;
use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Radio;
use Typecho\Widget\Helper\Form\Element\Text;
use Typecho\Widget\Helper\Form\Element\Textarea;
use Utils\Helper;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * typecho的waline评论插件
 *
 * @package walineJson
 * @author 刘铭熙
 * @version 1.0.0
 * @link http://typecho.org
 */
class Plugin implements PluginInterface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * @throws Exception
     */
    public static function activate()
    {
        try {
            Plugin::installs();
        } catch (Db\Exception | Exception $e) {
            throw new Exception('插件启动失败'.$e);
        }

        Helper::addRoute('comment_route', '/api/comment','walineJson_Action' ,'comment');


    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * @throws Exception
     */
    public static function deactivate()
    {
        try {
            Plugin::uninstall();
        } catch (Db\Exception | Exception $e) {
            throw new Exception('插件禁用失败'.$e);
        }

        Helper::removeRoute('comment_route');
    }

    /**
     * 获取插件配置面板
     *
     * @param Form $form 配置面板
     */
    public static function config(Form $form)
    {
        /** 邮箱 */
        $mail = new Text('mail', null, null, _t('邮箱地址'),_t('管理员邮箱地址'));
        $form->addInput($mail);

        /** 用户昵称· */
        $name = new Text('name', null, null, _t('用户昵称'));
        $form->addInput($name);
        /** 评论违禁词· */
        $sensitive = new Textarea('sensitive', null, '傻逼 脑残', _t('违禁词列表，用空格隔开'));
        $form->addInput($sensitive);
        /** 评论状态· */
        $status = new Radio('status', [
            'approved' => '通过',
            'waiting' => '待审核'
        ], 'waiting', _t('默认评论状态'), _t('默认待审核'));
        $form->addInput($status);
    }

    /**
     * 个人用户的配置面板
     *
     * @param Form $form
     */
    public static function personalConfig(Form $form)
    {
    }

    /**
     * 插件实现方法
     *
     * @access public
     * @return void
     */
    public static function render()
    {

    }

    /**
     * @throws Exception
     * @throws Db\Exception
     */
    public static function installs()
    {
        $db= Db::get();
        $prefix = $db->getPrefix();
        $is_rid = $db->query('describe '.$prefix.'comments rid');
        $is_rid = $db->fetchAll($is_rid);
        if ($is_rid[0]['Field']==null)
        {

            try {
                $sql = 'alter table '.$prefix.'comments add rid int(10) default null';
                $db->query($sql);
                return '插入字段成功，插件启动成功';
            } catch (Db\Exception $e) {
                throw new Exception('插入字段rid失败'.$e);
            }

        }elseif($is_rid[0]['Field']=='rid')
        {
            return '检测到字段，插件启动成功';
        }

    }

    /**
     * @throws Exception
     * @throws Db\Exception
     */
    public static function uninstall()
    {
        $db= Db::get();
        $prefix = $db->getPrefix();
        $is_rid = $db->query('describe '.$prefix.'comments rid');
        $is_rid = $db->fetchAll($is_rid);
        if ($is_rid[0]['Field']==null)
        {

            return '未检测到字段，插件禁用成功';

        }elseif($is_rid[0]['Field']=='rid')
        {
            try {
                $sql = 'alter table '.$prefix.'comments DROP COLUMN rid';
                $db->query($sql);
                return '移除字段成功，插件禁用成功';
            } catch (Db\Exception $e) {
                throw new Exception('移除字段rid失败'.$e);
            }

        }
    }


}
