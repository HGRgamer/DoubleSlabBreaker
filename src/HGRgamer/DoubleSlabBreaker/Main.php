<?php

declare(strict_types=1);

namespace HGRgamer\DoubleSlabBreaker;

use pocketmine\block\Slab;
use pocketmine\block\utils\SlabType;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\math\RayTraceResult;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener
{

    private const MAX_REACH_DISTANCE_CREATIVE = 13;
    private const MAX_REACH_DISTANCE_SURVIVAL = 7;

    public function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    /**
     * Priority LOWEST to ensure that we cancel the event before other plugins can modify/access it
     * Note: BlockBreakEvent is still fired but only for the broken part of the double slab
     * @priority LOWEST
     */
    public function onBlockBreak(BlockBreakEvent $event)
    {
        $block = $event->getBlock();
        $player = $event->getPlayer();

        if ($block instanceof Slab && $block->getSlabType() == SlabType::DOUBLE()) {
            $eyePos = $player->getEyePos();
            $direction = $player->getDirectionVector();

            $rayLength = $player->isCreative() ? self::MAX_REACH_DISTANCE_CREATIVE : self::MAX_REACH_DISTANCE_SURVIVAL; // Adjust as needed
            $endPos = $eyePos->addVector($direction->multiply($rayLength));

            // Calculate intersection with the block
            $hitResult = $block->calculateIntercept($eyePos, $endPos);

            if ($hitResult instanceof RayTraceResult) {
                $hitVector = $hitResult->hitVector->subtractVector($block->getPosition());

                $hitSlabType = ($hitVector->y > 0.5) ? SlabType::TOP() : SlabType::BOTTOM();
                $keepSlabType = ($hitSlabType === SlabType::TOP()) ? SlabType::BOTTOM() : SlabType::TOP();

                //for calling half slab break
                $originalBlock = clone $block;
                $block->setSlabType($hitSlabType);

                //this is the best way to do things hopefully without breaking other plugins
                if ($player->breakBlock($block->getPosition())) {
                    $event->cancel();
                    $block->getPosition()->getWorld()->setBlock($originalBlock->getPosition(), $originalBlock->setSlabType($keepSlabType));
                }else{
                    $block->setSlabType(SlabType::DOUBLE());
                }
            }
        }
    }
}
