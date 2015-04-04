<?php

namespace SocioChat\Cron;

use Core\Utils\DbQueryHelper;
use SocioChat\DAO\MailQueueDAO;
use SocioChat\DAO\OnlineDAO;
use SocioChat\DAO\PropertiesDAO;
use SocioChat\DAO\UserDAO;
use SocioChat\DI;
use SocioChat\Utils\Mail;

class ServiceOnlineMonitor implements CronService
{
    /**
     * @param array $options
     */
    public function setup(array $options)
    {

    }

    /**
     * @return boolean
     */
    public function canRun()
    {
        return true;
    }

    /**
     * @return string|null
     */
    public function getLockName()
    {
        return 'OnlineMonitor';
    }

    /**
     * @return string
     */
    public function getHelp()
    {
        return "Script to check online users\n";
    }

    public function run()
    {
		$message = MailQueueDAO::create();
	    $online = OnlineDAO::create()->getOnlineCount();
	    $config = DI::get()->getConfig();
	    $timeOut = $config->onlineMonitoringTimeout;

	    /** @var PropertiesDAO $props */
	    foreach (PropertiesDAO::create()->getRegisteredList() as $props) {
		    if (!$limit = $props->getOnlineNotificationLimit()) {
			    continue;
		    }

		    if (OnlineDAO::create()->getByUserId($props->getUserId())->getId()) {
			    continue;
		    }

		    if ((time() - $timeOut) < strtotime($props->getOnlineNotificationLast())) {
			    continue;
		    }
		    if ($online >= $limit) {
			    $user = UserDAO::create()->getById($props->getUserId());

			    $msg = "<h2>Достижение заданного онлайна в СоциоЧате</h2>
<p>Вы получили данное письмо, потому что пожелали уведомить вас, когда в чате будет более $limit человек.</p>
<p><a href=\"" . $config->domain->protocol . $config->domain->web . "\">Войдите в чат</a> и присоединяйтесь к общению!</p>";

			    $message
				    ->setEmail($user->getEmail())
				    ->setTopic('Sociochat.me - Заходите к нам!')
			        ->setMessage($msg);
			    $message->save();

			    $props->setOnlineNotificationLast(DbQueryHelper::timestamp2date());
			    $props->save(false);

			    echo "Sending online-limit-reached ($online >= $limit) message to {$message->getEmail()}";
		    }
        }
    }
}
