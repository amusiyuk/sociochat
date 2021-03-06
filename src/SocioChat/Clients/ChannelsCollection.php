<?php

namespace SocioChat\Clients;

use Core\BaseException;
use SocioChat\DI;
use SocioChat\Message\Msg;
use SocioChat\Response\MessageResponse;
use Core\TSingleton;

class ChannelsCollection
{
    use TSingleton;
    /**
     * @var Channel[]
     */
    private $channels;

    public function addChannel(Channel $channel)
    {
        if (!isset($this->channels[$channel->getId()])) {
            $this->channels[$channel->getId()] = $channel;
        }
        return $this;
    }

    /**
     * @param User $user
     */
    public function clean(User $user)
    {
        $channelId = $user->getChannelId();
        if (DI::get()->getUsers()->getClientsCount($channelId) == 0 && isset($this->channels[$channelId]) && $this->channels[$channelId]->isPrivate()) {
            unset($this->channels[$channelId]);
        }
    }

    public function getHistory(User $user)
    {
        if (!isset($this->channels[$user->getChannelId()])) {
            return [];
        }

        $channel = $this->channels[$user->getChannelId()];
        /* @var $channel Channel */

        return $channel->getHistory($user->getLastMsgId());
    }

    public function pushToHistory(MessageResponse $response)
    {
        if (!isset($this->channels[$response->getChannelId()])) {
            throw new BaseException('Channel id = ' . $response->getChannelId() . ' has not been initialized');
        }
        /* @var $channel Channel */
        $channel = $this->channels[$response->getChannelId()];

        return $channel->pushResponse($response);
    }

    /**
     * @param $id
     * @return Channel|null
     */
    public function &getChannelById($id)
    {
	    if (!isset($this->channels[$id])) {
		    $this->channels[$id] = null;
	    }
        return $this->channels[$id];
    }

    /**
     * @param $channelName
     * @return Channel|null
     */
    public function getChannelByName($channelName)
    {
        foreach ($this->channels as $channel) {
            if ($channel->getName() == $channelName) {
                return $channel;
            }
        }
    }

    public function getChannels()
    {
        return $this->channels;
    }

	public function exportChannels()
	{
		$list = [];

		foreach ($this->channels as $channel) {
			$responses = $channel->getHistory(0);
			foreach ($responses as $id => $response) {
				/** @var Msg $msg */
				$msg = $response['msg'];
				$responses[$id][Channel::MSG] = $msg->getMsg();
			}

			$list[$channel->getId()] = [
				'name' => $channel->getName(),
				'ownerId' => $channel->getOwnerId(),
				'isPrivate' => $channel->isPrivate(),
				'lastMsgId' => $channel->getLastMsgId(),
				'responses' => $responses
			];
		}

		return $list;
	}
}