<?php

declare(strict_types=1);

namespace cyrchanh\knockbacksync;

use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;

class Main extends PluginBase {

    private GroundStateTracker $tracker;
    private KnockbackHandler $knockbackHandler;

    public function onEnable(): void {
        $this->saveDefaultConfig();

        // All values are read from config.yml
        $config = $this->getConfig();

        $this->tracker = new GroundStateTracker(
            (int) $config->get("buffer_duration_ms", 1000)
        );

        $this->knockbackHandler = new KnockbackHandler(
            $this->tracker,
            (int) $config->get("ping_offset", 25),
            (float) $config->get("horizontal_kb", 0.4),
            (float) $config->get("vertical_kb_ground", 0.4),
            (float) $config->get("vertical_kb_air", 0.0)
        );

        $server = $this->getServer();
        $server->getPluginManager()->registerEvents($this->tracker, $this);
        $server->getPluginManager()->registerEvents($this->knockbackHandler, $this);

        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(
            function(): void {
                $this->tracker->cleanBuffers();
            }
        ), 100);
    }

    public function onDisable(): void {
        $this->tracker->clearAll();
    }
}
