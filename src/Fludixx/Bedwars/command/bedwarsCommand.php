<?php

/**
 * @author Fludixx
 * @version 2.1
 * @license MIT
 */

declare(strict_types=1);

namespace Fludixx\Bedwars\command;

use Fludixx\Bedwars\Arena;
use Fludixx\Bedwars\Bedwars;
use Fludixx\Bedwars\utils\Utils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;
use pocketmine\Server;

class bedwarsCommand extends Command {

	public $bedwars;

	public function __construct()
	{
		parent::__construct("bw",
			"Bedwars Command",
			"/bw [ARENANAME] [MODE 8*1...]", ["bedwars"]);
		$this->bedwars = Bedwars::getInstance();
        $this->setPermission("bw.admin");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if(($sender->hasPermission("bw.admin") or Server::getInstance()->isOp($sender->getName())) and $sender instanceof Player) {
			$player = Bedwars::$players[$sender->getName()];
			if(!isset($args[0]) or !isset($args[1]) or $args[0] == "help") {
				$sender->sendMessage(Bedwars::PREFIX."/bw [ARENANAME] [MODE 8*1...]");
				return TRUE;
			} else {
				$levelname = $args[0];
				$mode = $args[1];
				$mode = str_replace("x", "*", $mode);
				$maxplayers = eval("return ".$args[1].";");
				if((int)$args[1][0] > 8) {
					$player->sendMsg("You can't add more than 8 Teams");
					return FALSE;
				} else {
					if($this->bedwars->getServer()->getWorldManager()->loadWorld($levelname, true)) {
						$level = $this->bedwars->getServer()->getWorldManager()->getWorldByName($levelname);
						$arenadata = [
							'teams' => $mode[0],
							'ppt' => $mode[2],
							'mapname' => $levelname,
							'maxplayers' => $maxplayers,
							'spawns' => []
						];
						Bedwars::$provider->addArena($levelname, $arenadata);
						$player->setPos(-1);
						$sender->getInventory()->setItem(0, ItemFactory::getInstance()->get(35, Utils::teamIntToColorInt(1)));
						$sender->teleport($level->getSafeSpawn());
						$player->sendMsg("Please Place the Blocks to set the Team spawns");
						$player->sendMsg("verwende /leave um zum Spawn zu kommen");
						Bedwars::$arenas[$player->getPlayer()->getWorld()->getFolderName()] =
							new Arena($player->getPlayer()->getWorld()->getFolderName(),
								(int)$mode[2], (int)$mode[0], $sender->getWorld(), []);
						return TRUE;
					} else {
						$player->sendMsg("Error: 1 Argument must be a Levelname!");
						return FALSE;
					}
				}
			}
		} else {
			$sender->sendMessage(Bedwars::PREFIX."You don't have the Permissions to add Arenas");
			return FALSE;
		}
	}
}