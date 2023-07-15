<?php

declare(strict_types=1);

/**
 * Bedwars - LoadStatsTask.php
 * @author Fludixx
 * @license MIT
 */

namespace Fludixx\Bedwars\task;

use Fludixx\Bedwars\Bedwars;
use Fludixx\Bedwars\ranking\StatsInterface;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\thread\NonThreadSafeValue;
use pocketmine\utils\Config;

class LoadStatsTask extends AsyncTask {

    const MYSQL = 1;
    const JSON  = 2;

    /**
     * @var StatsInterface
     */
    protected $statsSystem;
    /**
     * @var LoadStatsTask
     */
    protected $type;

    /**
     * @var array
     */
    protected $data;

    /**
     * LoadStatsTask constructor.
     * @param StatsInterface $statsSystem
     * @param int            $type
     * @param NonThreadSafeValue          $data
     * StatsSystem is an Instance of an StatsSystem (StatsInterface) so the results can get stored.
     * The results will be saved in $statsSystem->stats as [PlayerIdentifier -> ['key' => value]...]
     */
    public function __construct(StatsInterface $statsSystem, int $type, ...$data)
    {
        $this->statsSystem = $statsSystem;
        $this->type = $type;
        $this->data = new NonThreadSafeValue($data);
    }

    public function onRun(): void
    {
        $allStats = [];
        switch ($this->type) {
            case self::MYSQL:
                $conn = mysqli_connect(Bedwars::$mysqlLogin['host'], Bedwars::$mysqlLogin['user'], Bedwars::$mysqlLogin['pass'], Bedwars::$mysqlLogin['db']);
                $result = $conn->query("SELECT * FROM 'players'");
                while($row = mysqli_fetch_assoc($result)){
                    $allStats[$row['id']] = $row;
                }
                break;
            case self::JSON:
                $configname = $this->data->deserialize()[0];
                $c = new Config($configname, Config::JSON);
                $allStats = $c->getAll();
                break;
        }
        $this->setResult($allStats);
    }

    public function onCompletion(): void
    {
        $this->statsSystem->stats = $this->getResult();
        Bedwars::getInstance()->getLogger()->notice("All stats were moved into memory!");
    }

}