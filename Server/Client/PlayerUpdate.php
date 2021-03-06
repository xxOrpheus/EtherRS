<?php
namespace Server\Client;

/**
 * @category RSPS
 * @package EtherRS
 * @author David Harris <lolidunno@live.co.uk>, Mitchell Murphy <mitchell@fl3x.co>
 * @copyright 2013 EtherRS
 * @version GIT: $Id:$
 * @link https://github.com/mitchellm/EtherRS/
 */

class PlayerUpdate extends \Server\Client\PlayerHandler {
	protected $player, $enc, $player_handler;
	protected $block, $out;

	public function __construct(\Server\Client\Player $player) {
		$this->player = $player;
		$this->out = $player->getOutstream();
		$this->enc = $player->getEncryptor();
	}

	public function sendBlock() {
		$this->block = new \Server\Network\Stream();
		$players = $this->getPlayers();
		
		$this->out->beginPacket($this->enc, 81);
		$this->out->iniBitAccess();
		
		$this->updateLocalMovement();
		if ($this->player->isUpdateRequired()) {
			$this->updateState(false, true);
		}
		
		$this->out->putBits(8, 0);
		foreach($players as $plr) {
			if(false == true) {
				PlayerUpdating.updateOtherPlayerMovement(other, out);
				if ($plr->isUpdateRequired()) {
					PlayerUpdating.updateState(false, false);
				}
			} else {
				$this->out->putBit(true);
				$this->out->putBits(2, 3);
			}
		}
		
		for($i = 0; $i < count($players); $i++) {
			if ($i >= 255) {
				//Player limit has been reached.
				break;
			}
			$other = $players[$i];
			if($other == null || $other == $player)
				continue;
							
			$this->addPlayer($other);
			$this->updateState($forceAppearance, $noChat);
		}
		
		if($this->block->getCurrentOffset() > 0) {
			$this->out->putBits(11, 2047);
			$this->out->finishBitAccess();
			$this->out->putBytes($this->block->getStream());
		} else {
			$this->out->finishBitAccess();
		}
		
		$this->out->finishPacket();
		//needs to be written to the socket
	}
	
	public function appendChat() {
		$this->out->putShort((($this->player->getChatColor() & 0xff) << 8) + ($this->player->getChatEffects() & 0xff));
		$this->out->putByte($this->player->getStaffRights());
		$this->out->putByteC( strlen($this->player->getChatText()) );
		$this->out->putBytesReverse($this->player->getChatText());
	}

	public function appendAppearance() {
		$this->out->putByte($this->player->getGender()); // Gender (0 = male, 1 = female) ??
		$this->out->putByte(0); // Skull icon

		$this->out->putByte(0); // Hat
		$this->out->putByte(0); // Cape
		$this->out->putByte(0); // Amulet
		$this->out->putByte(0); // Weapon
		$this->out->putByte(0); // Chest
		$this->out->putByte(0); // Shield

		$this->out->putShort(0x100 + 1); // Arms
		$this->out->putShort(0x100 + 1); // Legs
		$this->out->putShort(0x100 + 1); // Head
		$this->out->putShort(0x100 + 1); // Hands
		$this->out->putShort(0x100 + 1); // Feet
		$this->out->putShort(0x100 + 1); // Beard

		// called the "color loop". what are colors? possibly related to player looks. maybe clothes
		$this->out->putByte(1);
		$this->out->putByte(1);
		$this->out->putByte(1);
		$this->out->putByte(1);
		$this->out->putByte(1);
		$this->out->putByte(1);

		// the animation indicies
		$this->out->putShort($this->player->getAnims('stand')); // stand
		$this->out->putShort($this->player->getAnims('standTurn')); // stand turn
		$this->out->putShort($this->player->getAnims('walk')); // walk
		$this->out->putShort($this->player->getAnims('turn180')); // turn 180
		$this->out->putShort($this->player->getAnims('turn90CW')); // turn 90 cw
		$this->out->putShort($this->player->getAnims('turn90CCW')); // turn 90 ccw
		$this->out->putShort($this->player->getAnims('run')); // run

		$this->out->putLong(\Server\Misc::nameToLong($this->player->getUsername()));
		$this->out->putByte($this->player->getCombatLevel()); // Combat level.
		$this->out->putShort($this->player->getTotalLevel()); // Total level.

		// Append the block length and the block to the packet.
		$this->out->putByte($this->out->getCurrentOffset());
		$this->out->putBytes($this->out->getStream());

	}

	public function updateState($forceAppearance, $noChat) {
		$mask = 0x0;
		/**
		 * 
		 * Update masks:
		 * 
		 * 0x400: Makes the player appear to be walking while animating.
		 * 0x8:   For animations
		 * 0x4:   Player text above head
		 * 0x80:  Normal player text
		 * 0x1:   The player's current interacting entity.
		 * 0x10:  Player appearance updating
		 * 0x2:   The current player direction that they're facing.
		 * 0x20:  Hit update.
		 * 0x200: Hit update. Most likely associated with special attacking.
		 * 
		 */
		 
		
		if ($this->player->isChatUpdateRequired() && !$noChat) {
			$mask |= 0x80;
		}
		if ($this->player->isAppearanceUpdateRequired() || $forceAppearance) {
			$mask |= 0x10;
		}
		
		// Now, we write the actual mask.
		if ($mask >= 0x100) {
			$mask |= 0x40;
			$this->out->putShort($mask);
		} else {
			$this->out->putByte($mask);
		}
	
		if ($this->player->isChatUpdateRequired() && !$noChat) {
			$this->appendChat();
		}

		if ($this->player->isAppearanceUpdateRequired() || $forceAppearance) {
			$this->appendAppearance();
		}
	}
	
	public function addPlayer(\Server\Client\Player $other) {
		$this->out->putBits(11, 0);
		$this->out->putBit(true);
		$this->out->putBit(true);
		// Position delta = Misc.delta(player.getPosition(), other.getPosition());
		
		$this->out->putBits(5, 11);
		$this->out->putBits(5, 11);
	}
	
	public function updateLocalMovement() {
		$updateRequired = $this->player->isUpdateRequired();
		if($this->player->needsPlacement()) {
			$this->out->putBit(true);
			$x = 400;
			$y = 400;
			$this->appendPlacement($x, $y, 1, true, true);
		} else {
			$primaryDirection = -1;
			$secondaryDirection = -1;
			if($primaryDirection != -1) {
				$this->out->writeBit(true);
				if($secondaryDirection != -1) {
					$this->appendRun($primaryDirection, $secondaryDirection, $updateRequired);
				} else {
					$this->appendWalk($primaryDirection, $updateRequired);
				}
			} else {
				if($updateRequired) {
					$this->out->putBit(true);
					$this->appendStand();
				} else {
					$this->out->putBit(false);
				}
			}
		}	
	}
	
	public function appendStand() {
		$this->out->putBits(2,0);
	}
	
	public function appendRun($primaryDirection, $secondaryDirection, $updateRequired) {
		$this->out->putBits(2, 2);

		$this->out->putBits(3, $primaryDirection);
		$this->out->putBits(3, $secondaryDirection);
		$this->out->putBit($updateRequired);
	}
	
	public function appendWalk($primaryDirection, $updateRequired) {
		$this->out->putBits(2, 1);

		$this->out->putBits(3, $primaryDirection);
		$this->out->putBit($updateRequired);
	}
	
	public function appendPlacement($x, $y, $z, $discardMovementQueue, $attributesUpdate) {
			$this->out->putBits(2, 3);
			$this->out->putBits(2, $z);
			$this->out->putBit($discardMovementQueue);
			$this->out->putBit($attributesUpdate);
			$this->out->putBits(7, $y);
			$this->out->putBits(7, $x);
	}
}
