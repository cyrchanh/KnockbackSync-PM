<?php

declare(strict_types=1);

namespace cyrchanh\knockbacksync;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class KnockbackHandler implements Listener {

    private GroundStateTracker $tracker;
    private int $pingOffsetMs;
    private float $horizontalKb;
    private float $verticalKbGround;
    private float $verticalKbAir;

    public function __construct(
        GroundStateTracker $tracker,
        int $pingOffsetMs,
        float $horizontalKb,
        float $verticalKbGround,
        float $verticalKbAir
    ) {
        $this->tracker = $tracker;
        $this->pingOffsetMs = $pingOffsetMs;
        $this->horizontalKb = $horizontalKb;
        $this->verticalKbGround = $verticalKbGround;
        $this->verticalKbAir = $verticalKbAir;
    }

    /**
     * @priority HIGH
     */
    public function onEntityDamage(EntityDamageByEntityEvent $event): void {
        if ($event->isCancelled()) {
            return;
        }

        $victim = $event->getEntity();
        $attacker = $event->getDamager();

        if (!($victim instanceof Player) || !($attacker instanceof Player)) {
            return;
        }

        $event->setKnockBack(0.0);
        $this->applySyncedKnockback($victim, $attacker);
    }

        private function applySyncedKnockback(Player $victim, Player $attacker): void {
        $pingMs = $victim->getNetworkSession()->getPing() ?? 100;
        $lookbackMs = (int)($pingMs / 2) + $this->pingOffsetMs;
        $targetTime = (int)(microtime(true) * 1000) - $lookbackMs;
    
        $wasOnGround = $this->tracker->getGroundStateAt(
            $victim->getName(),
            $targetTime
        );
    
        $direction = $victim->getPosition()->subtractVector($attacker->getPosition());
        $horizontalDist = sqrt($direction->x ** 2 + $direction->z ** 2);
    
        if ($horizontalDist < 0.001) {
            $yaw = $attacker->getLocation()->getYaw();
            $dirX = -sin(deg2rad($yaw));
            $dirZ = cos(deg2rad($yaw));
        } else {
            $dirX = $direction->x / $horizontalDist;
            $dirZ = $direction->z / $horizontalDist;
        }
    
        $currentMotion = $victim->getMotion();
    
        $kbX = ($currentMotion->x / 2) + ($dirX * $this->horizontalKb);
        $kbZ = ($currentMotion->z / 2) + ($dirZ * $this->horizontalKb);
        $kbY = ($currentMotion->y / 2) + ($wasOnGround ? $this->verticalKbGround : $this->verticalKbAir);
    
        // Cap vertical KB just like PocketMine does
        if ($kbY > $this->verticalKbGround) {
            $kbY = $this->verticalKbGround;
        }
    
        $victim->setMotion(new Vector3($kbX, $kbY, $kbZ));
    }
}
