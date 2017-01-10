<?php
namespace server;

/**
 * 
 * @package server.Wsserver
 * @author mrlin <714480119@qq.com>
 */
class Wsserver extends Baseserver implements INetserver
{
    /**
     * chat
     * 
     * @var null 
     */
    protected $chat = null;

// ------------------------------------------------------------------------
    /**
     * @inheritdoc
     */
    public function __construct()
    {
        $this->serv = new \Swoole\Websocket\Server($this->addr, $this->port);
        $this->serv->set($this->config);

        $this->serv->on('open', array($this, 'onConnect'));
        $this->serv->on('message', array($this, 'onReceive'));
        $this->serv->on('task', array($this, 'onTask'));
        $this->serv->on('finish', array($this, 'onFinish'));
        $this->serv->on('close', array($this, 'onClose'));

        //init chat
        $this->chat = new Chat($this->serv);

        //start a server
        $this->serv->start();
    }

// ------------------------------------------------------------------------
    /**
     * 
     * static method
     * 
     * @noreturn
     */
    public static function run()
    {
        $serv = new self();
    }

// ------------------------------------------------------------------------
    /**
     * 
     * @inheritdoc
     */
    public function onConnect($serv, $req)
    {
        $username = isset($req->get['u']) ? $req->get['u'] : '';
        $email    = isset($req->get['e']) ? $req->get['e'] : '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($username))
        {
            $this->serv->close($req->fd);
            echo "ws client " .$req->fd. " have been closed forcedfully\r\n";
            goto end;
        }

        $user = array(
            'fd'        => $req->fd,
            'type'      => '2', //from ws client
            'identify'  => 'user',
            'logintime' => time(),
            'email'     => $email,
            'username'  => $username,
            'remoteip'  => $req->server['remote_addr'],            
        );

        $this->serv->task(json_encode(array(
            'fd'      => $req->fd,
            'task'    => 'login',
            'package' => $user,
        )));

        echo "ws client ". $req->fd ." connected\r\n";

        end:
        ;
    }

// ------------------------------------------------------------------------
    /**
     * 
     * 接收到的消息
     * 
     * The received msg package
     * 
     * {
     *     'type'  : '1',    // 1=>teminal, 2=>web, 3=>app
     *     'from'  : 'Andy', // 
     *     'proto' : 'ws',   // ws=>websocket, tcp=>tcp
     *     'to'    : '',     //
     *     'chat'  : 'u',    // u => someone, a => all
     *     'data'  : 'hello, world',
     * }
     * 
     * @inheritdoc
     */
    public function onReceive($serv, $req)
    {
        $data = array(
            'fd'      => $req->fd,
            'task'    => 'message',
            'package' => $req->data,
        );

        $this->serv->task(json_encode($data));

        echo "serv recv ws data=" .$req->data. "\r\n";
    }

// ------------------------------------------------------------------------
    /**
     *
     * @inheritdoc
     */
    public function onTask($serv, $taskId, $fromId, $data)
    {
        //返回ws端的报文格式
        $ret = array(
            'code' => 0,       // 错误代码
            'msg'  => '',      // 错误提示
            'data' => array(), // 消息响应报文
        );

        $data = json_decode($data, true);

        switch ($data['task'])
        {
            case 'login':
                //出席信息
                $pack = $this->chat->doLogin($data['package']);
                break;

            case 'message':
                //消息信息
                $pack = $this->chat->doMessage($data['package']);
                break;

            case 'logout':
                //退出信息
                $pack = $this->chat->doLogout($data['package']);
                break;
            
            default:
                $pack = $ret;
                break;
        }

        $this->chat->send($data['fd'], $data['package']);

        echo "worker[$fromId] get the task, taskId=$taskId\r\n";
    }

// ------------------------------------------------------------------------
    /**
     *
     * @inheritdoc
     */
    public function onFinish($serv, $taskId, $data)
    {
        echo "wss finished task taskId=$taskId\r\n";
    }

// ------------------------------------------------------------------------
    /**
     * 
     * @inheritdoc
     */
    public function onClose($serv, $fd)
    {
        $data = array(
            'fd'      => $fd,
            'task'    => 'logout',
            'package' => array(),
        );

        $this->serv->task(json_decode($data));

        echo "client $fd disconnected\r\n";
    }
}