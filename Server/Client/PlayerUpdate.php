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
				//	PlayerUpdating.updateOtherPlayerMovement(other, out);
				//	if (other.isUpdateRequired()) {
				//		PlayerUpdating.updateState(false, false);
				//	}
			} else {
				$this->out->putBit(true);
				$this->out->putBits(2, 3);
			}
		}
		
		for($i = 0; $i < count($players); $i++) {
			//	if (player.getPlayers().size() >= 255) {
			//		// Player limit has been reached.
			//		break;
			//	}
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
		$this->out->putByte(0); // Gender
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

		$this->out->putByte(1);
		$this->out->putByte(1);
		$this->out->putByte(1);
		$this->out->putByte(1);
		$this->out->putByte(1);
		$this->out->putByte(1);

		$this->out->putShort(0x328); // stand
		$this->out->putShort(0x337); // stand turn
		$this->out->putShort(0x333); // walk
		$this->out->putShort(0x334); // turn 180
		$this->out->putShort(0x335); // turn 90 cw
		$this->out->putShort(0x336); // turn 90 ccw
		$this->out->putShort(0x338); // run

		$this->out->putLong(\Server\Misc::nameToLong($this->player->getUsername()));
		$this->out->putByte(3); // Combat level.
		$this->out->putShort(0); // Total level.

		// Append the block length and the block to the packet.
		$this->out->putByte($this->out->getCurrentOffset());
		$this->out->putBytes($this->out->getStream());

	}

	public function updateState($forceAppearance, $noChat) {
		$mask = 0x0;
		
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