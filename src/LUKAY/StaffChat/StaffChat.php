<?php

namespace LUKAY\StaffChat;

use LUKAY\StaffChat\commands\StaffChatCommand;
use LUKAY\StaffChat\listener\PlayerChatListener;
use LUKAY\StaffChat\listener\PlayerJoinListener;
use LUKAY\StaffChat\listener\PlayerQuitListener;
use LUKAY\StaffChat\tasks\SendAsyncTask;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;

class StaffChat extends PluginBase {
    use SingletonTrait;

    private array $onlineStaffMembers = [];

    protected function onLoad(): void {
        $this->saveResource("config.yml");
        self::setInstance($this);
    }

    protected function onEnable(): void {
        $this->getServer()->getCommandMap()->register("UltraStaffChat", new StaffChatCommand("staffchat", "Write something in the Staff Chat", null, ["sc"]));
        $this->getServer()->getPluginManager()->registerEvents(new PlayerChatListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerJoinListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerQuitListener(), $this);
    }

    public function getConfig(): Config {
        return new Config($this->getDataFolder() . "config.yml", Config::YAML);
    }

    public function sendMessageToDiscord(Player $player, string $message): void {
        Server::getInstance()->getAsyncPool()->submitTask(new SendAsyncTask($player->getName(), $message, $this->getConfig()->get("webhook-url")));
    }

    public function isStaffMember(Player $player): bool {
        return array_key_exists($player->getName(), $this->onlineStaffMembers);
    }

    public function addOnlineStaffMember(Player $player): void {
        $this->onlineStaffMembers[$player->getName()] =  true;
    }

    public function removeOnlineStaffMember(Player $player): void {
        unset($this->onlineStaffMembers[$player->getName()]);
    }

    public function sendMessage(string $message):  void {
        $loader = StaffChat::getInstance();
        foreach ($loader->getServer()->getOnlinePlayers() as $player) {
            if ($this->isStaffMember($player)) {
                $player->sendMessage($message);
            }
        }
    }
}