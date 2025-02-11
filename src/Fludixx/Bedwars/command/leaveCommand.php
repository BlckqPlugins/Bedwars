<?php

/**
 * Bedwars - leaveCommandCommand.php
 * @author Fludixx
 * @license MIT
 */

declare(strict_types=1);

namespace Fludixx\Bedwars\command;

use Fludixx\Bedwars\Bedwars;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

class leaveCommand extends Command {

	public function __construct()
	{
		parent::__construct("leave",
			"Teleports you back to the Spawn",
			"/leave",  ["l"]);
        $this->setPermission(null);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if($sender instanceof Player) {
			$player = Bedwars::$players[$sender->getName()];
			$player->getPlayer()->setGamemode(0);
			$player->rmScoreboard($sender->getWorld()->getFolderName());
			$player->saveTeleport(Bedwars::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
			$player->setPos(0);
			$player->setSpectator(FALSE);
            $sender->getInventory()->setContents([
                0 => VanillaItems::IRON_SWORD()
            ]);
			$player->getPlayer()->getArmorInventory()->clearAll();
		}
	}

}