<?php

/**
 * Bedwars - StartCommand.php
 * @author Fludixx
 * @license MIT
 */

declare(strict_types=1);

namespace Fludixx\Bedwars\command;

use Fludixx\Bedwars\Bedwars;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class StartCommand extends Command {

	public function __construct()
	{
		parent::__construct("start", "Set the counter to 10", "/start", []);
        $this->setPermission("bw.start");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if($sender->hasPermission("bw.start") and $sender instanceof Player) {
			Bedwars::$arenas[$sender->getWorld()->getFolderName()]->setCountdown(10);
		}
	}

}