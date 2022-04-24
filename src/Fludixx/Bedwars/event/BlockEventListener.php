<?php

/**
 * Bedwars - BlockEventListener.php
 * @author  Fludixx
 * @license MIT
 */

declare(strict_types=1);

namespace Fludixx\Bedwars\event;

use Fludixx\Bedwars\Arena;
use Fludixx\Bedwars\Bedwars;
use Fludixx\Bedwars\utils\Utils;
use pocketmine\block\tile\Bed;
use pocketmine\block\tile\Sign;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\utils\SignText;
use pocketmine\color\Color;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\particle\DustParticle;
use pocketmine\world\particle\EntityFlameParticle;

class BlockEventListener implements Listener
{

    public function placeBlock(BlockPlaceEvent $event)
    {
        $player = Bedwars::$players[$event->getPlayer()->getName()];
        $pos = $player->getPos();
        if ($pos < 0 and !($pos < -9)) {
            $event->cancel();
            $levelname = $player->getPlayer()->getWorld()->getFolderName();
            $spawnid = abs($pos);
            $player->getPlayer()->getInventory()->setItem(0, ItemFactory::getInstance()->get(35, Utils::teamIntToColorInt($spawnid + 1)));
            $arenadata = Bedwars::$provider->getArena($levelname);
            $arenadata['spawns']["$spawnid"]['x'] = $event->getBlock()->getPosition()->getX();
            $arenadata['spawns']["$spawnid"]['y'] = $event->getBlock()->getPosition()->getY();
            $arenadata['spawns']["$spawnid"]['z'] = $event->getBlock()->getPosition()->getZ();
            Bedwars::$provider->addArena($levelname, $arenadata);
            if($spawnid >= (int)$arenadata['teams']) {
                $player->sendMsg("You reached the limit of Teams for this Arena!");
                Bedwars::$arenas[$arenadata['mapname']] =
                    new Arena($arenadata['mapname'], (int)$arenadata['ppt'], (int)$arenadata['teams'], $player->getPlayer()->getWorld(), $arenadata['spawns']);
                Bedwars::getInstance()->getServer()->dispatchCommand($player->getPlayer(), "leave");
            }
            $player->sendMsg("You placed the Spawn of " . Utils::teamIntToColorInt($spawnid) . ". (Next Team: " . Utils::ColorInt2Color(Utils::teamIntToColorInt
                ($spawnid + 1)) . ")");
            $player->setPos($pos - 1);
        } else if ($pos === 0) {
            if (!$player->canBuild()) $event->cancel();
        } else {
            if (!in_array($event->getBlock()->getId(), Bedwars::BLOCKS))
                $event->cancel();
            $pos = $event->getBlock()->getPosition()->asVector3();
            $pos->y -= 2;
            $tile = $event->getBlock()->getPosition()->getWorld()->getTile($pos);
            if ($tile instanceof Sign) {
                $player->sendMsg("You can't place blocks there");
                $event->cancel();
            }
        }
    }

    public function blockBreak(BlockBreakEvent $event)
    {
        $player = Bedwars::$players[$event->getPlayer()->getName()];
        $pos = $player->getPos();
        if ($pos === 0) {
            if (!$player->canBuild()) $event->cancel();
        } else if ($pos < 0) {
            $event->cancel();
            $event->getBlock()->getPosition()->getWorld()->addParticle($event->getBlock()->getPosition()->asVector3(), new BlockBreakParticle($event->getBlock()));
            if ($pos === -11 and $event->getBlock() instanceof Sign) {
                $sign = $event->getBlock()->getPosition()->getWorld()->getTile($event->getBlock()->getPosition()->asVector3());
                if ($sign instanceof Sign) {
                    $event->cancel();
                    $sign->setText(new SignText([
                        Bedwars::NAME,
                        $player->getKnocker(),
                        "§a? §7/ §c" . (Bedwars::$arenas[$player->getKnocker()]->getPlayersProTeam() *
                         Bedwars::$arenas[$player->getKnocker()]->getTeams()), "???"
                    ]));
                    $player->setPos(0);
                }
            }
        } else if ($pos > 0) {
            $tile = $event->getBlock()->getPosition()->getWorld()->getTile($event->getBlock()->getPosition()->asVector3());
            if ($tile instanceof Bed) {
                $color = $tile->getColor()->id();
                $team = Utils::ColorIntToTeamInt($color);
                if ($team === $pos) {
                    $event->cancel();
                    $player->sendMsg("You can't break your own Bed!");
                    $player->setVaule("ttbb", $player->getVaule("ttbb") + 1);
                    if ((int)$player->getVaule("ttbb") > 5) {
                        $player->sendMsg("Stop it. You can't");
                        $player->setVaule("ttbb", 0);
                    }
                } else {
                    Bedwars::$statsSystem->set($player->getPlayer(), 'beds', (int)Bedwars::$statsSystem->get($player->getPlayer(), 'beds') + 1);

                    Bedwars::$arenas[$player->getPlayer()->getWorld()->getFolderName()]->destroyBed($team);
                    $event->setDrops([]);
                    $tile->getPosition()->getWorld()->addParticle($tile->getPosition()->asVector3(), new EntityFlameParticle());
                    $tile->getPosition()->getWorld()->addParticle($tile->getPosition()->asVector3(), new DustParticle(new Color(255, 255, 255)));
                }
            } else if (!in_array($event->getBlock()->getId(), Bedwars::BLOCKS))
                $event->cancel();
        }
    }

}
