### 客户端安装

```html
<!-- 使用短链接获取最新的默认文件 -->
<script src="//cdn.jsdelivr.net/npm/@waline/client"></script>

<!-- 省略版本号以自动应用最新版本 -->
<script src="//cdn.jsdelivr.net/npm/@waline/client/dist/Waline.min.js"></script>

<!-- 或者手动指定最新版本 -->
<script src="//cdn.jsdelivr.net/@waline/clien@latest/dist/Waline.min.js"></script>
```

## HTML 引入 (客户端)

在你的网页中进行如下设置:

1. 使用 CDN 引入 Waline: `//cdn.jsdelivr.net/npm/@waline/client`。

2. 创建 `<script>` 标签使用 `Waline()` 初始化，并传入必要的 `el` 与 `serverURL` 选项。

    - `el` 选项是 Waline 渲染使用的元素，你可以设置一个字符串形式的 CSS 选择器或者一个 HTMLElement 对象。
    - `serverURL` 是服务端的地址，即上一步获取到的值。



   ```html
   <head>
     <!-- ... -->
     <script src="//cdn.jsdelivr.net/npm/@waline/client"></script>
     <!-- ... -->
   </head>
   <body>
     <!-- ... -->
     <div id="waline"></div>
     <script>
       Waline({
         el: '#waline',
         serverURL: 'https://your-domain.vercel.app',
       });
     </script>
   </body>
   ```

3. 评论服务此时就会在你的网站上成功运行 🎉

### 配置

**大部分配置和waline官网配置一样**

```html
<script>
    Waline({
        el: '#waline',
        serverURL: 'your domain name/api/',
        path:'<?php $this->cid() ?>',
        dark:'body[class="uk-light"]',
        avatar: 'retro',
        copyright: false,
        math:true,
        highlight: 'github-dark-dimmed',
        login:'disable'

    });
</script>
```