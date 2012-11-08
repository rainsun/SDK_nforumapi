<?php
/**
 * SDK for nForum
 * nforumapi类实现了nForum中所有api.
 * 
 * @package api
 * @author rainsun@cqupt(rainsuner@gmail.com)
 * @copyright 2012 CQUPT BBS
 */


/*
 * How to use?
 *
 * $bbs = new nforumapi( 'APP Key' , 'SEC Key' );
 * $bbs->setUser( 'username' , 'password' );
 * print_r($bbs->public_timeline());
 *
 */


class nforumapi
{
    /**
     * 应用程序的id(appid)
     * @var string
     */
    private $akey = '';
    /**
     * 应用程序的私钥secret
     * @var string
     */
    private $skey = '';
    /**
     * 接口调用方法
     * 0:POST 1:GET
     * @var integer
     */
    private $method = 0;
    
    /**
     * API调用地址
     * @var string
     */
    private $base = 'http://api.bbs.cqupt.edu.cn';
    /**
     * 返回结果类型
     * .(json|xml)
     * @var string
     */
    private $rType = '.json';
    /**
     * refer消息类型
     * at:@信息 relay:回复提醒
     * @var string
     */
    private $type = 'at';
    
    function __construct($akey = '', $skey = '')
    {
        $this->akey = $akey;
        $this->skey = $skey;
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
    }
    
    function setUser($name, $pass)
    {
        $this->user['name'] = $name;
        $this->user['pass'] = $pass;
        curl_setopt($this->curl, CURLOPT_USERPWD, "$name:$pass");
    }
    
    /**
     *  获取指定用户的信息
     *  HTTP请求方式 GET
     *
     *  @param id string 必选 合法的用户id
     *  @return 所查询的用户元数据 
     *  @link SDK::User 
     **/
    function public_queryid($id)
    {
        return $this->call_method('user', 'query', $id);
    }
    /**
     * 用户退出
     * HTTP请求方式 GET
     * 
     * @return 当前登录用户元数据
     * @link SDK:User
     */
    function user_logout()
    {
        return $this->call_method('user', 'logout');
    }
    /**
     * 获取指定分区的信息
     * HTTP请求方式 GET
     * 
     * @param name string 必选 合法的分区名称
     * @return SDK::section sub_array  
     *	    1.(array)sub_section  当前分区包含的分区目录名数组
     *	    2.(array)board 当前分区包含的版面元数据数组
     * @link SDK:Board
     */
    function section_name($name)
    {
        return $this->call_method('section', $name);
    }
    /**
     * 获取制定版面的信息
     * HTTP请求方式 GET
     * @param name string 必选 合法的版面名称
     * @param mode int 可选 表示版面文章列表的模式，分别是
     *			= 0 以id为顺序列表
     *			= 1 文摘区列表
     *			= 2 同主题(web顺序)列表
     *			= 3 精华区列表
     *			= 4 回收站列表
     *			= 5 废纸篓列表
     *			= 6 同主题(发表顺序)列表
     * @param count int 可选 每页文章数 最小1 最大50 默认30
     * @param page int 可选 文章页数 默认1
     * @return SDK::board sub_array 版面元数据且其中还包括如下
     *	    1.(array)article 当前版面模式所包含的文章元数组
     *	    2.(pagination)pagination 单签版面模式分页信息
     */
    function board_name($name)
    {
        return $this->call_method('board', $name);
    }
    
    /**
     * 获取指定文章信息
     * HTTP请求方式 GET
     * @param name string 必选 合法的版面名称
     * @param id int 必选 文章或主题id
     * @param mode int 可选 文章所在的版面模式，如果访问文摘，保留，回收站，垃圾箱中的文章需要指定mode，通过文章所在的postion访问
     * @return SDK::article 文章元数据
     */
    function article_board_id($name, $id, $mode = '')
    {
        $this->postdata[] = 'mode=' . $mode;
        return $this->call_method('article', $name, $id);
    }
    /**
     * 获取指定主题信息
     * HTTP请求方式 GET
     * @param name string 必选 合法版面名称
     * @param id int 必选 文章或者主题id
     * @param count int 可选 每页文章的数量 int 最小1 最大50 默认10
     * @param page int 可选 主题文章的页数 默认1
     * @return SDK::article 文章元数据且其中包括以下
     *	    1.(array)article 当前主题包含的文章元数据数组
     *	    2.(array)pagination 当前主题分页信息
     */
    function threads_board_id($name, $id, $count = 10, $page = 1)
    {
        $this->postdata[] = 'count=' . $count;
        $this->postdata[] = 'page=' . $page;
        return $this->call_method('threads', $name, $id);
    }
    /**
     * 发布新的文章/主题
     * HTTP请求方式 POST
     * @param name string 必选 合法的版面名称
     * @param title string 必选 新文章标题
     * @param content string 必选 新文章的内容，可以为空
     * @param reid int 可选 新文章回复其他文章的id
     * @param signature int 可选 新文章使用的签名档，0为不使用，从1开始表示使用第几个签名档，默认使用上一次的签名档
     * @param email int 可选 新文章是否启用回文转寄，0不使用，1使用，默认为0
     * @param anonymous int 可选 新文章是否匿名发表，0不匿名，1匿名，默认为0，若为匿名发表需要版面属性允许匿名发文
     * @param outgo int 可选 新文章是否对外转信，0不转信，1转信，默认为0，需要版面属性允许转信
     * @return SDK::article 已发表的文章元数据
     */
    function article_board_post($name, $title, $content, $reid = '', $signature = 0, $email = 1, $anonymous = 0, $outgo = 0)
    {
        $this->postdata[] = 'title=' . $title;
        $this->postdata[] = 'reid=' . $reid;
        $this->postdata[] = 'signature=' . $signature;
        $this->postdata[] = 'email=' . $email;
        $this->postdata[] = 'anonymous=' . $anonymous;
        $this->postdata[] = 'outgo=' . $outgo;
        return $this->call_method('article', $name, 'post');
    }
    /**
     * 转寄指定的文章/主题
     * HTTP请求方式 POST
     * @param name string 必选 合法的版面名称
     * @param id int 必选 文章或主题id
     * @param target string 必选 合法的用户id
     * @param threads int 可选 是否合集转寄该文章所在的主题，0:否，1:同主题合集转寄，默认为0
     * @param noref int 可选 在threads为1时，是否不保留引文，0:保留，1:不保留，默认为0
     * @param noatt int 可选 是否不保留附件，0:保留，1:不保留，默认为0
     * @param noansi int 可选 是否不保留ansi字符，0:保留，1:不保留，默认为0
     * @param big5 int 可选 是否使用big5编码，0:不使用，1:使用，默认为0
     * @return SDK::article 转寄文章的元数据
     */
    function article_board_forward_id($name, $id, $target, $threads = 0, $noref = 0, $noatt = 0, $noansi = 0, $big5 = 0)
    {
        $this->postdata[] = 'target=' . $target;
        $this->postdata[] = 'threads=' . $threads;
        $this->postdata[] = 'noref=' . $noref;
        $this->postdata[] = 'noatt=' . $noatt;
        $this->postdata[] = 'noansi=' . $noansi;
        $this->postdata[] = 'big5=' . $big5;
        return $this->call_method('article', $name . '/forward', $id);
    }
    /**
     * 更新指定文章/主题
     * HTTP请求方式 POST
     * @param name string 必选 合法的版面名称
     * @param id int 必选 文章或主题id
     * @param title string 必选 修改后的文章标题
     * @param content string 必选 修改后的文章内容
     * @return SDK::article 已更新的文章元数据
     */
    function article_board_update_id($name, $id, $title, $content)
    {
        $this->postdata[] = 'title=' . $title;
        $this->postdata[] = 'content=' . $content;
        return $this->call_method('article', $name . '/update', $id);
    }
    /**
     * 删除指定文章
     * HTTP请求方式 POST
     * @param name string 必选 合法的版面名称
     * @param id int 文章或主题id
     * @return SDK::article 已删除文章的元数据
     */
    function article_board_delete_id($name, $id)
    {
        return $this->call_method('article', $name . '/delete', $id);
    }
    /**
     * 获取指定的信箱信息
     * HTTP获取方式 GET
     * @param box string 必选 只能为inbox|outbox|deleted中的一个，分别是收件箱|发件箱|回收站
     * @param count int 可选 每页信件数量 int 最小1 最大50 默认20
     * @param page int 可选 当前信箱的分页信息 默认1
     * @return array 
     *	    1.(string)description 信箱类型描述，包括：收件箱，发件箱，废纸篓
     *	    2.(array)mail 当前信箱所包含的信件元数据数组
     *	    3.(array)pagination 当前信箱分页信息
     */
    function mail_box($box, $count, $page)
    {
        $this->postdata[] = 'count=' . $count;
        $this->postdata[] = 'page=' . $page;
        return $this->call_method('mail', $box);
    }
    /**
     * 信箱属性信息，包括是否有新邮件
     * HTTP请求方式 GET
     * @return SDK::Mailbox 信箱元数据
     */
    function mail_info()
    {
        return $this->call_method('mail', 'info');
    }
    /**
     * 获取指定的信件信息
     * HTTP请求方式 GET
     * @param box string 必选 只能为inbox|outbox|deleted中的一个，分别是收件箱|发件箱|回收站
     * @param num int 必选 信件在信箱的索引,为信箱信息的信件列表中每个信件对象的index值
     * @return SDK::Mail 信件元数据
     */
    function mail_box_num($box, $num)
    {
        return $this->call_method('mail', $box, $num);
    }
    /**
     * 发送新信件
     * HTTP请求方式 POST
     * @param id string 必选 合法的用户id
     * @param title string 可选 信件的标题
     * @param content string 可选 信件的内容
     * @param signature int 可选 信件使用的签名档，0为不使用，从1开始表示使用第几个签名档，默认使用上一次的签名档
     * @param backup int 可选 是否备份到发件箱，0为不备份，1为备份，默认为0
     * @return boolean status 成功为true
     */
    function mail_send($id, $title = '', $content = '', $signature = 0, $backup = 1)
    {
        $this->postdata[] = 'id=' . $id;
        $this->postdata[] = 'title=' . $title;
        $this->postdata[] = 'content=' . $content;
        $this->postdata[] = 'signature=' . $signature;
        $this->postdata[] = 'backup=' . $backup;
        return $this->call_method('mail', 'send');
    }
    /**
     * 转寄指定信箱的邮件
     * HTTP请求方式 POST
     * @param box string 必选 只能为inbox|outbox|deleted中的一个，分别是收件箱|发件箱|回收站
     * @param num int 必选 信件在信箱的索引,为信箱信息的信件列表中每个信件对象的index值
     * @param target string 必选 合法用户id
     * @param noansi int 可选 是否不保留ansi字符，0:保留，1:不保留，默认为0
     * @param big5 int 可选 是否使用big5编码，0:不使用，1:使用，默认为0
     * @return SDK::Mail 所转寄邮件元数据
     */
    function mail_box_forword_num($box, $num, $target, $noansi = 1, $big5 = 0)
    {
        $this->postdata[] = 'target=' . $target;
        $this->postdata[] = 'noansi=' . $noansi;
        $this->postdata[] = 'big5=' . $big5;
        return $this->call_method('mail', $box . '/forword', $num);
    }
    /**
     * 回复指定信箱中的信件
     * HTTP请求方式 POST
     * @param box string 必选 只能为inbox|outbox|deleted中的一个，分别是收件箱|发件箱|回收站
     * @param num int 必选 信件在信箱的索引,为信箱信息的信件列表中每个信件对象的index值
     * @param title string 可选 信件的标题
     * @param content string 可选 信件的内容
     * @param signature int 可选 信件使用的签名档，0为不使用，从1开始表示使用第几个签名档，默认使用上一次的签名档
     * @param backup int 可选 是否备份到发件箱，0为不备份，1为备份，默认为0
     * @return SDK::Mail 所回复的邮件元数据
     */
    function mail_box_reply_num($box, $num, $title = '', $content = '', $signature = 0, $backup = 1)
    {
        $this->postdata[] = 'title=' . $title;
        $this->postdata[] = 'content=' . $content;
        $this->postdata[] = 'signature=' . $signature;
        $this->postdata[] = 'backup=' . $backup;
        return $this->call_method('mail', $box . 'reply', $num);
    }
    /**
     * 获取指定的信件
     * HTTP请求方式	    POST
     * @param box string 必选 只能为inbox|outbox|deleted中的一个，分别是收件箱|发件箱|回收站
     * @param num int 必选 信件在信箱的索引,为信箱信息的信件列表中每个信件对象的index值
     * @return SDK::Mail 已删除信件的元数据
     */
    function mail_box_delete_num($box, $num)
    {
        return $this->call_method('mail', $box . 'delete', $num);
    }
    /**
     * 获取指定提醒类型列表
     * HTTP请求方式 GET
     * @param type string 必选 只能为at|reply中的一个，分别是@我的文章和回复我的文章
     * @param count int 可选 每页提醒文章数量
     * @param page int 可选 提醒列表的页数
     * @return sub_array 
     *	    1.(string)description 提醒类型描述，包括：@我的文章，回复我的文章
     *	    2.(array)article 当前提醒列表所包含的提醒元数据数组
     *	    3.(array)pagination 当前提醒列表的分页信息
     */
    function refer_type($type, $count = 20, $page = 1)
    {
        $this->postdata[] = 'count=' . $count;
        $this->postdata[] = 'page=' . $page;
        return $this->call_method('refer', $type);
    }
    /**
     * 获取指定类型提醒的属性信息
     * HTTP获取方式 GET
     * @param type string 必选 只能为at|reply中的一个，分别是@我的文章和回复我的文章
     * @return array 
     *	    1.(boolean)enable 当前类型的提醒是否启用
     *	    2.(int)new_count 当前类型的新提醒个数
     */
    function refer_type_info($type)
    {
        return $this->call_method('refer', $type, 'info');
    }
    /**
     * 设置指定提醒为已读
     * HTTP请求方式 POST
     * @param type string 必选 只能为at|reply中的一个，分别是@我的文章和回复我的文章
     * @param index int 可选 提醒的索引，为提醒元数据中的index值。如果此参数不存在则设置此类型的所有提醒已读
     * @return SDK::Mail|boolean 如果存在index参数，则返回设置为已读的提醒元数据；如果不存在返回status=true
     */
    function refer_type_setread_index($type, $index)
    {
        return $this->call_method('refer', $type . 'setRead', $index);
    }
    /**
     * 删除指定提醒
     * HTTP获取方式 POST
     * @param type string 必选 只能为at|reply中的一个，分别是@我的文章和回复我的文章
     * @param index int 可选 提醒的索引，为提醒元数据中的index值。如果此参数不存在则删除此类型的所有提醒
     * @return SDK::Mail|boolean 如果存在index参数，则返回已删除的提醒元数据；如果不存在返回status=true
     */
    function refer_type_delete_index($type, $index)
    {
        return $this->call_method('refer', $type . 'delete', $index);
    }
    //===========================================================================================================================	
    function upload($status, $file)
    {
        curl_setopt($this->curl, CURLOPT_POST, 1);
        $this->postdata[] = 'status=' . urlencode($status);
        $this->postdata[] = "pic=@" . $file;
        
        return $this->call_method('statuses', 'update');
    }
    
    
    function call_method($method, $action, $args = '')
    {
        $this->postdata[] = 'appkey=' . $this->akey;
        $this->postdata[] = 'secret=' . $this->skey;
        if ($this->method)
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, join('&', $this->postdata));
        else
            curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, join('&', $this->postdata));
        
        $enter = $action ? '/' : '';
        $url   = $this->base . $method . '/' . $action . $enter . $args . $this->rType;
        curl_setopt($this->curl, CURLOPT_URL, $url);
        
        return json_decode(curl_exec($this->curl), true);
        
    }
    
    function __destruct()
    {
        curl_close($this->curl);
    }
    
}


?>
