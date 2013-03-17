var createBlogDetail = function(blog_post) {
    var l_post_source = L('post_source');

    // prepare style, make it pretty
    style = '<link rel="stylesheet" href="http://twitter.github.com/bootstrap/1.4.0/bootstrap.min.css">';
    style = style + "<style>body {margin:10px} h1 {margin-bottom:0;} h5 { margin-bottom:10px;color: #aaa; }</style>";

    if (config.FIX_IMG_POST == '1') {
        style = style + "<style>img { display:block; margin: 10px 0; max-width: 300px;}</style>";
    }

    // prepare header
    header = "<h1>" + blog_post.title + "</h1>";
    header = header + "<h5>" + date("F j, Y", strtotime(blog_post.date)) + ' | ' + blog_post.author.nickname + "</h5>";

    post_content = style + header + blog_post.content;

    var Window = Ti.UI.createWindow({
            title: blog_post.title_plain,
            navBarHidden: false,
            barColor: skin.POST_BAR_COLOR,
            barImage: skin.POST_BAR_IMAGE,
            layout: "vertical",
        }),
        webPost = Ti.UI.createWebView({
            visible: false,
            height: 370,
            width: 320,
            html: post_content
        });

    Window.add(webPost);
    webPost.show();

    var url = blog_post.url;
    var email_subject = blog_post.title_plain;
    var email_body = '<p>' + l_post_source + ': <a href="' + url + '">' + url + "</a></p>\n\n" + post_content;

    create_share_button(Window, url, email_subject, email_body);

    return Window;
};