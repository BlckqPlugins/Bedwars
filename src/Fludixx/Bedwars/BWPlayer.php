<?php

/**
 * Bedwars - BWPlayer.php
 * @author Fludixx
 * @license MIT
 */

declare(strict_types=1);

namespace Fludixx\Bedwars;

use Fludixx\Bedwars\utils\Scoreboard;
use pocketmine\entity\Location;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\world\Position;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\Player;

/**
 * Class BWPlayer
 * @package Fludixx\Bedwars
 * In the BWPlayer class info about an Player will be saved, for example teams
 */
class BWPlayer {

	/** @var Player */
	protected $player;
	/** @var int */
	protected $pos;
	/** @var int */
	protected $team;
	/** @var string */
	protected $knocker = NULL;
	/** @var int */
	protected $knockedAt = 0;
	/** @var bool */
	protected $fuerGold = TRUE;
	/** @var array */
	protected $extraData = [];
	/** @var bool */
	protected $canBuild = FALSE;
	/** @var bool */
	protected $isSpectator = FALSE;

	/**
	 * BWPlayer constructor.
	 * @param Player $player
	 */
	public function __construct(Player $player)
	{
		$this->player = $player;
		$this->pos = 0;
		$this->team = 0;
	}

    /**
     * @return bool
     */
	public function canBuild() : bool {
	    return $this->canBuild;
    }

    /**
     * @param bool $canBuild
     */
    public function setCanBuild(bool $canBuild): void
    {
        $this->canBuild = $canBuild;
    }

	/**
	 * @return int
	 */
	public function getPos() : int
	{
		return $this->pos;
	}

	/**
	 * @return int
	 */
	public function getTeam() : int
	{
		return $this->team;
	}

	/**
	 * @param int $pos
	 */
	public function setPos(int $pos) : void
	{
		$this->pos = $pos;
	}

	/**
	 * @param int $team
	 */
	public function setTeam(int $team) : void
	{
		$this->team = $team;
	}

	/**
	 * @return Player
	 */
	public function getPlayer() : Player
	{
		return $this->player;
	}

    /**
     * @param bool $isSpectator
     */
    public function setSpectator($isSpectator = TRUE)
    {
        $this->isSpectator = $isSpectator;
    }

    /**
     * @return bool
     */
    public function isSpectator()
    {
        return $this->isSpectator;
    }

	/**
	 * @param string $knocker
	 */
	public function setKnocker(string $knocker) : void
	{
		$this->knocker = $knocker;
	    $this->knockedAt = time();
	}

	/**
	 * @return string|null
	 */
	public function getKnocker()
	{
	    if(time() - $this->knockedAt > 15) return null;
		else return $this->knocker;
	}

	/**
	 * @return string
	 */
	public function getName() : string {
		// TODO Implement Nicks
		return $this->player->getName();
	}

	/**
	 * @param Position $position
	 */
	public function saveTeleport(Position $position) {
        $this->player->teleport(new Location($position->getX(), $position->getY(), $position->getZ(), $position->getWorld(), 0.0, 0.0));
	}

	public function sendMsg(string $msg) {
		$this->player->sendMessage(Bedwars::PREFIX."$msg");
	}

	/**
	 * @param Scoreboard $sb
	 */
	public function sendScoreboard(Scoreboard $sb) {
		$pk = new RemoveObjectivePacket();
		$pk->objectiveName = $sb->objName;
		$this->player->getNetworkSession()->sendDataPacket($pk);
		$pk = new SetDisplayObjectivePacket();
		$pk->displaySlot = "sidebar";
		$pk->objectiveName = $sb->objName;
		$pk->displayName = $sb->title;
		$pk->criteriaName = "dummy";
		$pk->sortOrder = 0;
		$this->player->getNetworkSession()->sendDataPacket($pk);
		foreach ($sb->lines as $num => $line) {
			if($line === "")
				$line = str_repeat("\0", $num);
			$entry = new ScorePacketEntry();
			$entry->objectiveName = $sb->objName;
			$entry->type = 3;
			$entry->customName = " $line ";
			$entry->score = $num;
			$entry->scoreboardId = $num;
			$pk = new SetScorePacket();
			$pk->type = 0;
			$pk->entries[$num] = $entry;
			$this->player->getNetworkSession()->sendDataPacket($pk);
		}
	}

	public function die() {
		$levelname = $this->player->getWorld()->getFolderName();
		if(Bedwars::$arenas[$levelname]->getBeds()[$this->pos]) {
			$this->player->setHealth(20.0);
			$this->player->getHungerManager()->setFood(20.0);
			$this->player->getInventory()->clearAll();
			$this->player->getArmorInventory()->clearAll();
			$pos = Bedwars::$arenas[$levelname]->getSpawns()[Bedwars::$players[$this->player->getName()]->getPos()];
			$this->player->teleport($pos);
		} else {
		    $this->setPos(0);
		    $this->rmScoreboard($this->player->getWorld()->getFolderName());
            $this->player->getInventory()->setContents([
                0 => VanillaItems::IRON_SWORD(),
            ]);
			$this->player->getArmorInventory()->clearAll();
			$this->saveTeleport(Bedwars::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
		}
	}

    /**
     * @param string $objname
     */
	public function rmScoreboard(string $objname) {
		$pk = new RemoveObjectivePacket();
		$pk->objectiveName = $objname;
		$this->player->getNetworkSession()->sendDataPacket($pk);
	}

    /**
     * @return bool
     */
	public function isForGold() : bool {
	    return $this->fuerGold;
    }

    /**
     * @param bool $state
     */
    public function setForGold(bool $state = TRUE) {
	    $this->fuerGold = $state;
    }

    /**
     * @param $key
     * @param $value
     */
    public function setVaule($key, $value) {
	    if(!isset($this->extraData[$key]))
	        $this->extraData[$key] = 0;
	    $this->extraData[$key] = $value;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getVaule($key) {
	    return $this->extraData[$key] ?? 0;
    }

    public function getRandomTeam(Arena $arena): int {
        $randomteam = mt_rand(1, $arena->getTeams());
        $tc = 0;
        foreach ($arena->getPlayers() as $p) {
            if (Bedwars::$players[$p->getName()]->getTeam() === $randomteam) {
                $tc++;
            }
        }
        if($tc >= $arena->getPlayersProTeam()) {
            return $this->getRandomTeam($arena);
        }
        return $randomteam;
    }

}
