<?php

declare(strict_types=1);

namespace cyrchanh\knockbacksync;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\player\Player;

class GroundStateTracker implements Listener {

    /** @var array<string, array<int, bool>> */
    private array $buffer = [];
    private int $bufferDurationMs;

    public function __construct(int $bufferDurationMs) {
        $this->bufferDurationMs = $bufferDurationMs;
    }

    public function onDataPacketReceive(DataPacketReceiveEvent $event): void {
        $packet = $event->getPacket();
        if (!($packet instanceof PlayerAuthInputPacket)) {
            return;
        }

        $player = $event->getOrigin()->getPlayer();
        if ($player === null) {
            return;
        }

        $name = $player->getName();
        $nowMs = (int)(microtime(true) * 1000);
        $onGround = $this->detectOnGround($packet, $player);

        $this->buffer[$name][$nowMs] = $onGround;
    }

    public function onPlayerQuit(PlayerQuitEvent $event): void {
        unset($this->buffer[$event->getPlayer()->getName()]);
    }

    private function detectOnGround(PlayerAuthInputPacket $packet, Player $player): bool {
        $pos = $packet->getPosition()->subtract(0, 1.62, 0);
        $blockBelow = $player->getWorld()->getBlockAt(
            (int)floor($pos->x),
            (int)floor($pos->y - 0.01),
            (int)floor($pos->z)
        );

        return $blockBelow->isSolid();
    }

    public function getGroundStateAt(string $playerName, int $targetTimeMs): bool {
        if (!isset($this->buffer[$playerName]) || empty($this->buffer[$playerName])) {
            return true;
        }

        $closestTime = null;
        $closestDiff = PHP_INT_MAX;

        foreach ($this->buffer[$playerName] as $timestamp => $onGround) {
            $diff = abs($timestamp - $targetTimeMs);
            if ($diff < $closestDiff) {
                $closestDiff = $diff;
                $closestTime = $timestamp;
            }
        }

        return $closestTime !== null ? $this->buffer[$playerName][$closestTime] : true;
    }

    public function cleanBuffers(): void {
        $cutoff = (int)(microtime(true) * 1000) - $this->bufferDurationMs;

        foreach ($this->buffer as $name => &$entries) {
            foreach ($entries as $timestamp => $state) {
                if ($timestamp < $cutoff) {
                    unset($entries[$timestamp]);
                }
            }
            if (empty($entries)) {
                unset($this->buffer[$name]);
            }
        }
    }

    public function clearAll(): void {
        $this->buffer = [];
    }
}