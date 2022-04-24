<?php

/**
 * Bedwars - SignTask.php
 * @author Fludixx
 * @license MIT
 */

declare(strict_types=1);

namespace Fludixx\Bedwars\task;

use Fludixx\Bedwars\Arena;
use Fludixx\Bedwars\Bedwars;
use pocketmine\block\tile\Sign;
use pocketmine\block\utils\SignText;
use pocketmine\block\WallSign;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class SignTask extends Task {

    /**
     * This functions refreshes all the signs without this task running no sign will get updated what would break the Server
     */
    public function onRun(): void
	{
		$level = Bedwars::getInstance()->getServer()->getWorldManager()->getDefaultWorld();
		foreach ($level->getLoadedChunks() as $chunk) {
            foreach ($chunk->getTiles() as $tile) {
                if ($tile instanceof Sign and $tile->getText()->getLine(0) === Bedwars::NAME) {
                    $levelname = $tile->getText()->getLine(1);
                    try {
                        $arena = Bedwars::$arenas[$levelname];
                        $players = count($arena->getPlayers());
                        if ($players < ((int)$arena->getPlayersProTeam() * (int)$arena->getTeams())) {
                            $state = $arena->getState() === Arena::STATE_OPEN ? Bedwars::JOIN : Bedwars::RUNNING;
                        } else {
                            $state = Bedwars::FULL;
                        }
                        if ($arena->getState() === Arena::STATE_INUSE) {
                            $state = Bedwars::RUNNING;
                        }

                        $tile->setText(new SignText([
                            $tile->getText()->getLine(0),
                            $tile->getText()->getLine(1),
                            "§a$players §7/ §c" . ((int)$arena->getPlayersProTeam() * (int)$arena->getTeams()),
                            $state
                        ]));
                    } catch (\ErrorException $ex) {
                        $tile->setText(new SignText(["Invalid Sign", "", "", ""]));
                    }
                    Server::getInstance()->getWorldManager()->getWorld($tile->getPosition()->getWorld()->getId())->setBlock($tile->getPosition()->asVector3(), $tile->getBlock(), false);
                }
            }
		}
	}

}