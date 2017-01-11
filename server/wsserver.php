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
     * 
     * chat
     * 
     * @var null 
     */
    public $chat = null;

    /**
     * 
     * The connctions list
     * 
     * @var array
     */
    public $connections = array();

// ------------------------------------------------------------------------
    /**
     * @inheritdoc
     */
    public function __construct()
    {
        //init chat
        $this->chat = new Chat($this);

        $this->serv = new \Swoole\Websocket\Server($this->addr, $this->port);
        $this->serv->set($this->config);

        $this->serv->on('open', array($this, 'onConnect'));
        $this->serv->on('message', array($this, 'onReceive'));
        $this->serv->on('task', array($this, 'onTask'));
        $this->serv->on('finish', array($this, 'onFinish'));
        $this->serv->on('close', array($this, 'onClose'));

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
     * chat/?u=xxxx&e=sample@xx.com&k=md5('xxx')
     *
     * u=>用户名, e=>邮箱, k=>后台管理Key
     * 
     * @inheritdoc
     */
    public function onConnect($serv, $req)
    {
        $get             = isset($req->get) ? $req->get : array();
        $get['remoteip'] = $req->server['remote_addr'];

        $this->serv->task(json_encode(array(
            'fd'      => $req->fd,
            'task'    => 'login',
            'package' => $get,
        )));

        $this->connections[$req->fd] = $req->fd;

        echo "ws client ". $req->fd ." connected\r\n";
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
     * handle the task job
     * 
     * @inheritdoc
     */
    public function onTask($serv, $taskId, $fromId, $data)
    {
        $data = json_decode($data, true);

        switch ($data['task'])
        {
            case 'login':
                //出席信息
                $this->chat->doLogin($data['fd'], $data['package']);
                break;

            case 'message':
                //消息信息
                $this->chat->doMessage($data['fd'], $data['package']);
                break;

            case 'logout':
                //退出信息
                $this->chat->doLogout($data['fd'], $data['package']);
                break;
        }

        echo "worker[$fromId] get the task, taskId=$taskId\r\n";

        return true;
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

        $this->serv->task(json_encode($data));

        echo "client $fd disconnected\r\n";
    }
}