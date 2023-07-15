<?php

/**
 * Bedwars - ChestListener.php
 * @author Fludixx
 * @license MIT
 */

namespace Fludixx\Bedwars\event;

use Fludixx\Bedwars\Bedwars;
use Fludixx\Bedwars\utils\Utils;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\inventory\Inventory;
use pocketmine\player\Player;

class ChestListener
{

    protected $bedwars;
    protected $inv;
    protected $player;

    public function __construct(Bedwars $main, Inventory $inv, Player $player)
    {
        $this->bedwars = $main;
        $this->inv = $inv;
        $this->player = $player;
    }

    public function onTransaction(DeterministicInvMenuTransaction $transaction){
        $player = $this->player;
        $itemClickedOn = $transaction->getItemClicked();

		if($itemClickedOn->getId() == 35) {
			$team = Utils::ColorIntToTeamInt($itemClickedOn->getDamage());
			$teamname = Utils::ColorInt2Color($itemClickedOn->getDamage());
			$arena = Bedwars::$arenas[$player->getWorld()->getFolderName()];
			$maxTeamMembers = $arena->getPlayersProTeam();
			$playersInTeam = 0;
			$playersInOtherTeams = 0;
			foreach($player->getWorld()->getPlayers() as $p) {
				$pteam = Bedwars::$players[$p->getName()]->getTeam();
				if($pteam == $team) {
					$playersInTeam++;
				} else {
					$playersInOtherTeams++;
				}
			}
			if ($playersInTeam >= $maxTeamMembers) {
				$player->sendMessage(Bedwars::PREFIX . "Team $teamname is already full!");
				$join = false;
			} else {
				if ($playersInTeam == 0 && $playersInOtherTeams == 0) {
					$player->sendMessage(Bedwars::PREFIX . "You entered the $teamname Team!");
					$join = true;
                } elseif ($playersInTeam >= 1 && $playersInOtherTeams == 0) {
                    $player->sendMessage(Bedwars::PREFIX . "You can't join the $teamname Team");
                    $join = false;
				} elseif ($playersInTeam =! 0 && $playersInOtherTeams != 0 && $playersInTeam < $maxTeamMembers) {
					$player->sendMessage(Bedwars::PREFIX . "You joined the $teamname Team");
					$join = true;
				}
			}
			if($join) {
				Bedwars::$players[$player->getName()]->setTeam($team);
			}
            $player->removeCurrentWindow();
        }
	}

}