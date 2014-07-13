<?php

namespace SocioChat\Controllers\Helpers;

use SocioChat\Clients\ChannelsCollection;
use SocioChat\Clients\User;
use SocioChat\Clients\UserCollection;
use SocioChat\Message\MsgToken;
use SocioChat\Response\MessageResponse;

class MainChatPrivateHandler
{
	public static function run(User $user, UserCollection $users, ChannelsCollection $chats)
	{
		if (!$user->isInPrivateChat()) {
			return;
		}

		self::moveUsersToPublic($user, $users);
		self::informYouselfOnExit($user);

		ChannelNotifier::uploadHistory($user, $users, true);
		ChannelNotifier::indentifyChat($user);

		$chats->clean($user);
	}

	/**
	 * @param User $user
	 * @param UserCollection $users
	 */
	private static function moveUsersToPublic(User $user, UserCollection $users)
	{
		$partners = $users->getUsersByChatId($user->getChannelId());

		$response = (new MessageResponse())
			->setTime(null)
			->setMsg(MsgToken::create('UserLeftPrivate', $user->getProperties()->getName()))
			->setDualChat('exit')
			->setGuests($partners)
			->setChannelId($user->getChannelId());

		$users
			->setResponse($response)
			->notify();

		foreach ($partners as $pUser) {
			$pUser->setChannelId(1);
			$pUser->save();
		}
	}

	private static function refreshGuestListOnNewChat(User $user, UserCollection $users)
	{
		$response = (new MessageResponse())
			->setTime(null)
			->setGuests($users->getUsersByChatId($user->getChannelId()))
			->setChannelId($user->getChannelId());

		$users
			->setResponse($response)
			->notify(false);
	}

	private static function informYouselfOnExit(User $user)
	{
		$response = (new MessageResponse())
			->setChannelId($user->getChannelId())
			->setTime(null)
			->setDualChat('exit')
			->setMsg(MsgToken::create('ReturnedToMainChat'));

		(new UserCollection())
			->attach($user)
			->setResponse($response)
			->notify(false);
	}
} 