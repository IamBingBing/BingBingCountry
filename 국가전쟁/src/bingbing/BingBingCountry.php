<?php
namespace bingbing;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;
use onebone\economyapi\EconomyAPI;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\level\particle\HeartParticle;
use pocketmine\math\Vector3;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\level\Position;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\scheduler\Task;



class BingBingCountry extends PluginBase implements Listener{
	private $economy;
	
	
	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getLogger()->info("§b[국가전쟁]§f플러그인 가동!! 이루온라인 빙빙제작");
		@mkdir($this->getDataFolder());
		
		$this->countrylist = new Config($this->getDataFolder()."list.yml",Config::YAML,[
				"country" => array(),
				"countries" => 0,
		        "kings" => array(),
		    "warp" => array()
		]);
		$this->list = $this->countrylist->getAll();
		
		
		$this->warst = new Config($this->getDataFolder()."warsituation.yml",Config::YAML,["fight" => "false"]);
		$this->war = $this->warst->getAll();
		
		$this->countryname["country"] = new Config($this->getDataFolder()."country.yml",Config::YAML);
		
		
		$this->country["country"] = $this->countryname["country"]->getAll();
		
		$this->namedb["player"] = new Config($this->getDataFolder()."playerData.yml",Config::YAML);
		$this->name["player"] =  $this->namedb["player"]->getAll();
		
		$this->ydb = new Config($this->getDataFolder()."ddd.yml",Config::YAML);
		$this->y =  $this->ydb->getAll();
		
		$this->getServer()->getScheduler()->scheduleRepeatingTask( new info($this), 20 );
		$this->getServer()->getScheduler()->scheduleRepeatingTask( new damage($this), 60 );
		$this->getServer()->getScheduler()->scheduleRepeatingTask( new money($this), 20*60*60*12);
		
	}
	
	
	public function join(PlayerJoinEvent $event){
		$name = $event->getPlayer()->getName();
		$player = $event->getPlayer();
		if (!isset($this->name["player"][$name])){
		    
		    $this->name["player"][$name] =[];
		    $this->name["player"][$name]["sosok"] = "무소속";
		    $this->name["player"][$name]["sinbon"] = "평민";
		    $this->name["player"][$name]["okay"] = "null";
		    $this->name["player"][$name]["x"] = "null";
		    $this->name["player"][$name]["y"] = "null";
		    $this->name["player"][$name]["z"] = "null";
		    $this->name["player"][$name]["message"] = array();
		    $this->name["player"][$name]["list"] = array();
		      /*  "sosok" => "무소속",
		        "sinbon" => "평민",
		        "okay" => "0",
		        "number" => "",
		        "list" => array() */
		   
		    
			$this->save();
		}
		
		$this->name["player"][$name]["message"] = array();
		for ($a = 0; $a <count($this->name["player"][$name]["message"]); $a++ ){
		    $player->sendMessage("§b[§f국가전쟁§b]§f ".$this->name["player"][$name]["message"][$a]);
		}
	}
	
	public function move(PlayerMoveEvent$event){
	    $x =$event->getPlayer()->getFloorX();
	    $y =$event->getPlayer()->getFloorY();
	    $z = $event->getPlayer()->getFloorZ();
	    if ($event->getPlayer()->getLevel()->getBlock(new Position($x,$y-2,$z))->getId() == "41"){
	        $event->getPlayer()->getLevel()->setBlock(new Position($x,$y-2,$z) , Block::get(0));
	        $event->getPlayer()->sendMessage("§b[§f국가전쟁§b]§f 아 이느낌은 지뢰밟았다");
	        $event->getPlayer()->kill();
	    }
	    
	}
	
	
	public function place(BlockPlaceEvent$place){
		$block = $place->getBlock();
		$x = $block->getFloorX();
		$z = $block->getFloorZ();
		$y = $block->getFloorY();
		$name = $place->getPlayer()->getName();
		$name1 = $place->getPlayer();
		$b =& $this->list["countries"];
		if ($this->y($x,$z,$name1) == "true" and $place->getBlock()->getId() !== 169 and $place->getBlock()->getLevel()->getName() == "country" and $block->getId() !== 49 ){
			$place->getPlayer()->sendMessage("§b[§f국가전쟁§b]§f 내 국가의 영역이 아닙니다.");
			$place->setCancelled();
		}
		if( $place->getBlock()->getLevel()->getName() !== "country" and $place->getBlock()->getId() == "169" and $block->getId() !== 49){
			$place->setCancelled();
			$place->getPlayer()->sendMessage("§b[§f국가전쟁§b]§f 이월드에는 건국이 불가능합니다");
		}
		if ($this->name["player"][$name]["sosok"] == "king" and $place->getBlock()->getLevel()->getName() == "country" and $place->getBlock()->getId() == "169" and $block->getId() !== 49){
			$place->setCancelled();
			
		}
		if ($this->war["fight"] == "false"){
		    if ($place->getBlock()->getId() == "169" and $this->name["player"][$name]["sosok"] == "무소속" and $place->getBlock()->getLevel()->getName() == "country" and $this->y($x,$z,$name1) == "false"){
				
			    $this->name["player"][$name]["okay"] = "true";
			    $this->name["player"][$name]["sinbon"] = "king";
			    $this->name["player"][$name]["x"] = $x;
			    $this->name["player"][$name]["y"] = $y;
			    $this->name["player"][$name]["z"] = $z;
			    $this->list["countries"] = $this->list["countries"]+1;
			    
			    array_push($this->list["kings"], $name);
			    
			   /* $key = array(
			        
			        "king" => $name,
			        "people" => array($name),
			        "peoplemax" => 15,
			        "x" => $x,
			        "y" => $y,
			        "z" => $z,
			        "countryname" => "",
			        "maxhealth" => "1000",
			        "health" => "1000");
			        */
			    
			    
			    
			    
			    $place->getPlayer()->sendMessage("§b[§f국가전쟁§b]§f 이제 국가이름을 설정해주세요 /국가이름설정 <국가이름>");
				$place->getPlayer()->sendMessage("§b[§f국가전쟁§b]§f 한번 생성하면 바꿀수 없으니 신중히 선택하세요.");
				$this->save();
			}
			else if ($this->name["player"][$name]["sosok"] !== "무소속" and $this->y($x,$z,$name1) == "true" and  $block->getLevel()->getName() == "country" and $block->getId() !== 49){
			    $place->setCancelled();
			}
			
			else if ($block->getId() == "49" and $this->yy($x,$z,$name1) == "false" and
			    $name1->isOp() and 
			    $this->y($x,$z,$name1) == "false"){
			        $this->y =[];
			        $c = count($this->y);
			        $this->y[$c] = [];
			        $this->y[$c]["sosok"] = "무소속";
			        $this->y[$c]["x"] = $x;
			        $this->y[$c]["y"] = $y;
			        $this->y[$c]["z"] = $z;
			        $this->y[$c]["max"] = 3000;
			        $this->y[$c]["health"] = 3000;
			        $this->save();
			        $name1->sendMessage("§b[§f국가전쟁§b]§f 야만인의 진체를 완성했습니다");
			}
			
			
			
		}
		else if ($block->getId() == "169" and $block->getId() !== 49){
			$place->setCancelled();
			$place->getPlayer()->sendMessage("§b[§f국가전쟁§b]§f 전쟁중에는 건국이 불가능합니다");
		}
		else if ($block->getId() !== 49){
			$place->setCancelled();
			$place->getPlayer()->sendMessage("§b[§f국가전쟁§b]§f 전쟁중에는 건축이 불가능합니다");
		}
		if ($this->getsosok($place->getPlayer()) == "무소속" and $block->getLevel()->getName() == "country" and $block->getId() !== 169 and $block->getId() !== 49){
		    $place->getPlayer()->sendMessage("§b[§f국가전쟁§b]§f 내 국가의 영역이 아닙니다.");
		    $place->setCancelled();
		}
		
		
		
	}
	
	public function break(BlockBreakEvent$place){
		$block = $place->getBlock();
		$name = $place->getPlayer()->getName();
		$z = $block->getFloorZ();
		$x = $block->getFloorX();
		$name1 = $place->getPlayer();
		if($block->getId()  == "169"){
			$place->setCancelled();
		}
		if ($this->y($x,$z,$name1) == "true" and $place->getBlock()->getId() !== "169" and $place->getBlock()->getLevel()->getName() == "country"){
			$place->getPlayer()->sendMessage("§b[§f국가전쟁§b]§f 내 국가의 영역이 아닙니다.");
			$place->setCancelled();
			$this->save();
		}
		if ($this->getsosok($place->getPlayer()) == "무소속" and $block->getLevel()->getName() == "country" and $block->getId() !== 169){
		    $place->getPlayer()->sendMessage("§b[§f국가전쟁§b]§f 내 국가의 영역이 아닙니다.");
		    $place->setCancelled();
		}
		if ($this->war["fight"] == "true"){
		    $place->setCancelled();
		}
		
	}
	
	
	public function touch(PlayerInteractEvent$event){
	    
		
		$m = "§b[§f국가전쟁§b]§f";
		
		$name = $event->getPlayer()->getName();
		$b = $event->getBlock();
		$damage = @mt_rand(1,5);
		
		$x2 = $b->getFloorX();
		$y2 = $b->getFloorX();
		$z2 = $b->getFloorX();
		
		$sosok = $this->name["player"][$name]["sosok"];
		
		$counts = $this->list["countries"];
		
		$player = $event->getPlayer();
		
		$toucher = $player->getName();
		$touch = $event->getTouchVector();
		$country = $this->list["country"];
		
		if ($this->war["fight"] == "true" and $event->getBlock()->getId() == "169" and !empty($country) and $this->getsosok($player) !== "무소속" ){
		    
		    
		    
		    
		    
		    
			for ($a = 0; $a < $counts; $a++){
			    
			    $ab = $this->list["country"][$a];
			    $x = $this->country["country"][$ab]["x"];
				$y = $this->country["country"][$ab]["y"];
				$z = $this->country["country"][$ab]["z"];
				
				$name = $event->getPlayer()->getName();
				$mx = $this->country["country"][$this->getsosokS($name)]["x"];
				$my = $this->country["country"][$this->getsosokS($name)]["y"];
				$mz = $this->country["country"][$this->getsosokS($name)]["z"];
				
					if (		$mx == $b->getFloorX()
							and $my == $b->getFloorY()
							and $mz == $b->getFloorZ()){
											$event->getPlayer()->sendMessage($m." 같은 국가의 핵심은 공격할수 없습니다.");
											break;
					}							
					else if ($x == $b->getFloorX()
					    and $y == $b->getFloorY()
					    and $z == $b->getFloorZ()){
								    $this->country["country"][$ab]["health"] = $this->country["country"][$ab]["health"]-$damage;
									
								    $this->getServer()->broadcastMessage($m.$ab."국가의 체력이 이제 ".$this->country["country"][$ab]["health"]."이 남았습니다.");
									for ($c =0; $c < 5; $c++){
									    $x1 = mt_rand($x-0.5, $x+0.5); 
									    $y1 =mt_rand($y-0.5, $y+0.5);
									    $z1 =mt_rand($z-0.5, $z+0.5);
										$player->getLevel()->addParticle(new HeartParticle(new Vector3($x1,$y1+1,$z1)));
									}
									EconomyAPI::getInstance()->addMoney($player, $damage*5000);
									
									$player->sendMessage($m." 당신의 돈이 ".EconomyAPI::getInstance()->myMoney($toucher)."이 되었습니다");
									$this->save();
									if ($this->country["country"][$ab]["health"] <= 0){
									    for ($g =0; $g < count($this->country["country"][$ab]["people"]); $g++){
									        $this->name["player"][$this->country["country"][$ab]["people"][$g]]["sosok"] = "무소속";
									        
									    }
									    $this->name["player"][$this->country["country"][$ab]["king"]]["sinbon"] = "평민";
										unset($this->list["country"][$ab]);
										unset($this->country["country"][$ab]);
										$this->getServer()->broadcastMessage($m.$ab."국가는 이제 "."멸망합니다");
										
										$this->save();
										break;
									}
									else {
										break;
									}
								}
					
				}
				
				
				
				
				
			}
			else {
			    for ($f = 0; $f < count($this->y); $f++){
			    if ($b->getFloorX() == $this->y[$f]["x"] and 
			        $b->getFloorY() == $this->y[$f]["y"] and 
			        $b->getFloorZ() == $this->y[$f]["z"] and $event->getBlock()->getLevel()->getName() == "country" and $this->y[$f]["sosok"] !== $this->name["player"][$name]["sosok"] ){
			            $this->y[$f]["health"] = $this->y[$f]["health"]-$damage;
			            
			            $this->getServer()->broadcastMessage($m.$this->y[$f]["sosok"]."소속의 야만인성 체력이 이제 ".$this->y[$f]["health"]."이 남았습니다.");
			            for ($c =0; $c < 5; $c++){
			                $x2 = mt_rand($x2-0.5, $x2+0.5);
			                $y2 =mt_rand($y2-0.5, $y2+0.5);
			                $z2 =mt_rand($z2-0.5, $z2+0.5);
			                $player->getLevel()->addParticle(new HeartParticle(new Vector3($x2,$y2+1,$z2)));
			            }
			            if ($this->y[$f]["health"]<= 0){
			                $this->y[$f]["sosok"] = $this->name["player"][$name]["sosok"];
			                $this->y[$f]["health"] = 1000;
			                $event->getPlayer()->sendMessage($m."야만인의 소속이 당신의 소속이 되었습니다.");
			            }
			    }
			    else if ($this->war["fight"] == "false" and $event->getBlock()->getId() == "169"){
			        $player->sendMessage("§b아직 전쟁이 시작하지 않았습니다");
			    }
			    }
			    
			}
			
				
			}
	
	
	public function onCommand(CommandSender $sender, Command $command,string $label, array $args):bool{
			$name = $sender->getName();
			$count = $this->list["countries"];
			$m = "§b[국가전쟁]§f";
			$warp =$this->list["warp"][$this->name["player"][$sender->getName()]["sosok"]];
			if ($command->getName() == "무소속"){
			    foreach ($this->getServer()->getOnlinePlayers() as $player ){
			        if ($args[0] == $player->getName()){
			            $this->name["player"][$player->getName()]["sosok"] = "무소속";
			            $player->sendMessage("무소속이 되셨습니다.");
			            return true;
			    }
			    }
			}
			
			if ($command->getName() == "국가워프"){
			    for ($v =0; $v <3; $v++){
			        $sender->teleport(new Vector3($warp[0], $warp[1], $warp[2] , "country"));
			        
			    }
			    return true;
			    
			}
			if ($command->getName() == "국가스폰"){
			    if ($this->name["player"][$name]["sinbon"] == "king"){
			        $this->list["warp"][$this->name["player"][$name]["sosok"]] = [];
			        $this->list["warp"][$this->name["player"][$name]["sosok"]][0] = $sender->getFloorX();
			        $this->list["warp"][$this->name["player"][$name]["sosok"]][1] = $sender->getFloorY();
			        $this->list["warp"][$this->name["player"][$name]["sosok"]][2] = $sender->getFloorZ();
			        
			    }
			    return true;
			    
			}
		if ($command->getName() == "국가"){
			
			switch($args[0]){
				case "리스트":
					if (!empty($this->list["country"])){
					$sender->sendMessage($m."=============국가목록==============");
					
					for  ($list = 0; $list < $count; $list++){
					    $a = $list+1;
					    $sender->sendMessage($m.$a."번쨰".$this->list["country"][$list]);
					}
					$sender->sendMessage($m."================================");
					break;
					}
					else {
						$sender->sendMessage($m."현제 생성된 국가가 없습니다.");
						break;
					}
					
				default:
					$sender->sendMessage("§b===========[§f국가전쟁§b]=============");
					$sender->sendMessage("§b[국가전쟁]§f/국가 정보 로 내소속 확인");
					$sender->sendMessage("§b[국가전쟁]§f/국가 리스트 로 국가들 확인");
					$sender->sendMessage("§b===============================");
					break;
			}
			return true;
			
		}
		if ($command->getName() == "국가이름설정"){
			$name = $sender->getName();
			$x =$this->name["player"][$name]["x"];
			$y =$this->name["player"][$name]["y"];
			$z =$this->name["player"][$name]["z"];
			if ($this->name["player"][$name]["sinbon"] == "king" and $this->name["player"][$name]["okay"] == "true"){
				
				
				
				
				if($this->issame($args[0]) == "false"){
					
					
					$sender->sendMessage("§b[§f국가전쟁§b]§f 국가이름이". $args[0]."으로 성공적으로 설정되었습니다. 국가설정이 마무리되었습니다 국가가 건국되었습니다");
					$this->country["country"][$args[0]] = [];
					$this->country["country"][$args[0]]["king"] = $name;
					$this->country["country"][$args[0]]["people"] = array($name);
					$this->country["country"][$args[0]]["peoplemax"] = 15;
					$this->country["country"][$args[0]]["x"] = $x;
					$this->country["country"][$args[0]]["y"] = $y;
					$this->country["country"][$args[0]]["z"] = $z;
					$this->country["country"][$args[0]]["countryname"] = $args[0];
					$this->country["country"][$args[0]]["maxhealth"] = 3000;
					$this->country["country"][$args[0]]["health"] = 3000;
					$this->name["player"][$name]["sosok"] = $args[0];
				    $this->name["player"][$name]["okay"] = "null";
					array_push($this->list["country"], $args[0]);
					$this->save();
					return true;
					
				}
			
			
			else if ($this->name["player"][$name]["sinbon"] !== "king"){
				$sender->sendMessage("§b[§f국가전쟁§b]§f 당신은 왕이 아닙니다.");
				return true;
				
			}
			}
			else if (!empty($this->country["country"][$this->name["player"][$name]["sosok"]]["countryname"])){
				$sender->sendMessage("§b[§f국가전쟁§b]§f 이미 국가이름이 정해져 있습니다");
			}
			return true;
			
		}
		
		if ($command->getName() == "국가원"){
			$name = $sender->getName();
			switch ($args[0]){
				case "초대":
					if (isset($args[1])){
					    foreach ( $this->getServer()->getOnlinePlayers() as $player) {
					    if ($this->name["player"][$name]["sinbon"] == "king"){
						    if ( count($this->country["country"][$this->getsosok($sender)]["people"]) < $this->country["country"][$this->getsosok($sender)]["peoplemax"]){
						        if ($this->ispeople($sender,$args[1]) == "false" and $this->name["player"][$args[1]]["sosok"] == "무소속" and $args[1] == $player->getName()){
									
									$n = $args[1];
									
									$player->sendMessage("§b[§f국가전쟁§b]§f ".$this->getsosok($sender)."국가로부터 초대받았습니다 .");
									$player->sendMessage("§b[§f국가전쟁§b]§f 수락을 원하시면 /수락 [국가이름] 을 해주세요");
									array_push($this->name["player"][$n]["list"], $this->name["player"][$name]["sosok"]);
									$this->save();
									break;
									
								}
								else if ($this->ispeople($sender,$args[1]) == "true"){
									$sender->sendMessage("§b[§f국가전쟁§b]§f 이미 당신의 국가원입니다");
									$this->save();
									break;
									
								}
								else if ($this->name["player"][$args[1]]["sosok"] !== "무소속"){
									$sender->sendMessage("§b[§f국가전쟁§b]§f 초대받는 사람이 다른국가에 소속되어있습니다");
									$this->save();
									break;
									
								}
							}
							else if(count($this->country["country"][$this->getnumber($sender)]["people"]) >= $this->country["country"][$this->getnumber($sender)]["peoplemax"]){
								$sender->sendMessage("§b[§f국가전쟁§b]§f 국가인원의 한계치 입니다");
								break;
							}
							else if ($args[1] !== $player->getName()){
								$sender->sendMessage("§b[§f국가전쟁§b]§f 현제 서버에 없는 사람입니다");
								break;
							}
						}
						
					    }return true;
					}
					
					
					else {
						$sender->sendMessage("§b[§f국가전쟁§b]§f 초대할사람을 적어주세요.");
						return true;
						
						break;
					}
					
					
					
				case "강퇴":
				    if ($this->name["player"][$name]["sinbon"] == "king"){
						foreach ($this->getServer()->getOnlinePlayers() as $player){
								if ($this->ispeople($sender,$args[1]) == "true" and $args[1] !== $sender->getName()){
									
									$n = $args[1];
									if ($player->getName() == $n){
									   $player->sendMessage("§b[§f국가전쟁§b]§f ".$this->name["player"][$name]["countryname"]."국가로부터 강퇴되었습니다 .");
									   return true;
									   
									}
									for ($peo = 0; $peo <= count($this->list[$this->name["player"][$name["number"]]]["peoplemax"]); $peo++){
									    if ($n == $this->country["country"][$this->getsosok($sender)]["people"][$peo]){
									        unset($this->country["country"][$this->getsosok($sender)]["people"][$peo]);
											$this->name["player"][$n]["sosok"] == "무소속";
											$this->save();
											return true;
											
											break;
										}
									}
									
									
								}
								else if ($this->ispeople($sender,$args[1]) == "1"){
									$sender->sendMessage("§b[§f국가전쟁§b]§f 이미 당신의 국가원이 아닙니다");
									return true;
									return true;
									
									break;
								}
							}
							
						
					}
			}
		}
		if ($command->getName() == "초대"){
			$name = $sender->getName();
			switch ($args[0]){
				case "목록":
				    $c = count($this->name["player"][$name]["list"]);
					
					for ($x= 0; $x <=$c; $x++){
						$me = $this->name["player"][$name]["list"][$x];
						$sender->sendMessage("§b[§f국가전쟁§b]§f".$me ."국가");
					}
			}
			return true;
			
		}
		if($command->getName() == "수락"){
			$c = $this->list["countries"];
			$name = $sender->getName();
			for ($b = 0; $b<$c; $b++){
			    $config = $this->name["player"][$name]["list"][$b];
				
				if ($config == $args[0]){
				    $this->name["player"][$name]["sosok"] = $config;
				    array_push($this->country["country"][$config]["people"],$name);
				    $sender->sendMessage("§b[§f국가전쟁§b]§f 이제 ".$config."국가 소속입니다");
				    
				   
				    $this->name["player"][$name]["list"] = array();
				    return true;
				    
				    break;
				    $this->save();
				}
				return true;
				
				
			}
			if ($g == "true"){
			    $sender->sendMessage("§b[§f국가전쟁§b]§f 국가초대목록에 없는 국가이름입니다");
			    return true;
			    
			}
		}
		
		if ($command->getName() == "전쟁")
		{
			if ($args[0] == "시작"){
			    return true;
			    
				
				$m = "§b[§f국가전쟁§b]§f";
				if ($this->war["fight"] == "false" and $sender->isOp()){
					$this->getServer()->broadcastMessage($m."국가전쟁의 시작입니다.§a피터지게 싸우세요");
					$this->getServer()->broadcastMessage($m."이제  pvp가 어디에서든지 활성화 됩니다§a피터지게 싸우세요");
					return true;
					
					$this->war["fight"] = "true";
				}
				else{
					$sender->sendMessage("§b[§f국가전쟁§b]§f 이미 전쟁중입니다");
					return true;
					
				}
			}
			if ($this->war["fight"] == "true" and $sender->isOp()){
			if ($args[0] == "종료"){
			    return true;
			    
				$this->getServer()->broadcastMessage($m."국가전쟁의 종료입니다.싸움을 멈추세요");
				$this->getServer()->broadcastMessage($m."이제  pvp가 어디에서든지 비활성화 됩니다");
				$this->war["fight"] = "false";
			}
			
			}
			else {
			    return true;
			    
				$sender->sendMessage("§b[§f국가전쟁§b]§f 이미 평화롭습니다");
			}
		}
	}
	public function getnumber(Player $name){
		return $this->name["player"][$name->getName()]["sosok"];
	}
	public function getsosok(Player $name){
	    $a = $this->name["player"][$name->getName()]["sosok"];
		return  $a;
	}
	public function getsosokS($name){
	    $a = $this->name["player"][$name]["sosok"];
	    return  $a;
	}
	public function getsinbon(Player $name){
	    $b = $this->name["player"][$name->getName()]["sinbon"];
		return $b;
	}
	public function getcountryhealth(Player $name){
		
		if ($this->getsosok($name) !== "무소속"){
		    return $this->country["country"][$this->getsosok($name)]["health"];
		}
		else {
			return "없음";
		}
	}
	public function getcountrymaxhealth(Player $name){
		if ($this->getsosok($name) !== "무소속"){
		    return $this->country["country"][$this->getsosok($name)]["maxhealth"];
		}
	}
	public function issame(string $cn){
		$d ;
		$b = count($this->list["country"]);
		
		if(!empty($b)){
			for($a = 0; $a <= $b; $a++){
				if ($this->list["country"][$a] == $cn){
					$d = "true";
					break;
				}
				else{
					$d ="false";
				}
			}
			return $d;
		}
		else{
			return "false";
		}
	}
	public function ispeople(Player $name,string $pe){
		$g;
	    $c = count($this->country["country"][$this->getsosok($name)]["people"]);
		for($d =0; $d<$c; $d++) {
			if($pe == $this->country["country"][$this->getsosok($name)]["people"][$d]){
				$g = "true";
				break;
			}
			else {
				$g ="false";
			}
		}
		return $g;
		
	}
	public function y($x,$z, Player $name){
		$b = $this->list["country"];
		$g = $this->list["countries"];
		$name1 = $this->getsosok($name);
		 
		if(!empty($b)){
			if ($this->getsosok($name) == "무소속" ){ 
			    for ($a=0; $a < count($this->list["kings"]); $a++){
			        
			        $m= $this->list["country"][$a];
			        if ($x <= $this->country["country"][$m]["x"]+30 and
			            $this->country["country"][$m]["x"]-30<= $x and
			            $z <= $this->country["country"][$m]["z"]+30 and
			            $z >= $this->country["country"][$m]["z"]- 30 ){
			                $b = "true";
			                break;
			        }
			        else {
			            $b = "false";
			        }
			        return $b;
			        
			    }      
			}
			
		   else if ($x < $this->country["country"][$name1]["x"]+30 and
		       $this->country["country"][$name1]["x"]-30< $x and 
		       $z < $this->country["country"][$name1]["z"]+30 and
		       $z > $this->country["country"][$name1]["z"]- 30 ){
			     return "false";
		   }
			else {
			    return "true";
			}
			
			}
		
		else if ($this->getsosok($name) == "무소속" ){
		    return "false";
		    
		}
		    
		
		
	}
	public function yy($x,$z, Player $name){
	    $b = $this->y;
	    $name1 = $this->getsosok($name);
	    $cd = "false";
	    
	    if(!empty($b)){
	        
	        if (!$name->isOp()){
	            return "true";
	        }
	        else {
	        for ($a =0; $a< count($this->y); $a++){
	           if ($x < $this->y[$a]["x"]+30 and
	            $this->y[$a]["x"]-30< $x and
	            $z < $this->y[$a]["z"]+30 and
	            $z > $this->y[$a]["z"]- 30 ){
	                if ($this->y[$a]["sosok"] == $this->name[$name1]["sosok"]){
	                    $cd = "true";
	                   break;
	                }
	                
	                
	           }
	           }
	           return $cd;
	        }
	        
	        
	    }
	    
	    else if (!$name->isOp() ){
	        return "true";
	        
	    }
	    else {
	        return "false";
	    }
	}
	public function damage($x,$z, Player $name){
		    $b = $this->y;
		    $name1 = $this->getsosok($name);
		    $cd = "false";
		   if ($name->getLevel()->getName() == "country"){
		    if(!empty($b)){
		        for ($a =0; $a< count($this->y); $a++){
		            if ($x < $this->y[$a]["x"]+30 and
		                $this->y[$a]["x"]-30< $x and
		                $z < $this->y[$a]["z"]+30 and
		                $z > $this->y[$a]["z"]- 30 and $this->y[$a]["sosok"] == $this->name["player"][$name->getName()]["sosok"] ){
		                        $cd = "true";
		                        break;
		                    
		                    
		            }
		            else {
		                $cd =  "false";
		            }
		        }
		        return $cd;
		    
		  }
		  else{
		  return "false";
		  } 
		  }
		   
		   else {
		       "false";
		   }
		}
	
	
	public function onDisable(){
		$this->save();
	}
	public function save(){
		
			
	    $this->namedb["player"]->setAll($this->name["player"]);
	    $this->namedb["player"]->save();
			
		
			
		
	    $this->countryname["country"]->setAll($this->country["country"]);
	    $this->countryname["country"]->save();
			
		
		$this->warst->setAll($this->war);
		$this->warst->save();
		
		$this->countrylist->setAll($this->list);
		$this->countrylist->save();
		
		$this->ydb->setAll($this->y);
		$this->ydb->save();
		
	}
	public function money(){
	    for ($a =0; $a< count($this->y); $a++){
	     if ($this->y[$a]["sosok"] !== "무소속"){
	        EconomyAPI::getInstance()->addMoney($this->country["country"][$this->y[$a]["sosok"]]["king"], 150000);
	     array_push( $this->name["player"][$this->country["country"][$this->y[$a]["sosok"]]["king"]]["message"] , "§b[§f국가전쟁§b]§f 정복한 야만인의 땅에서 세금을 15만원 걷어 왔습니다.");
	    
	     }	
	    }
	    
	    }
	
	public function isOnline($name){
	    $onlines = implode(", ", array_map(function($player){ return $player->getName(); }, $this->getServer()->getOnlinePlayers())); 
		for ($on =0; $on < count($this->getServer()->getOnlinePlayers()); $on++){
			if (strtolower($onlines[$on]) == strtolower($name)){
				$d = "true";
						break;
			}
			else {
				$d = "false";
			}
		}
		return $d;
	}
	
}

class damage extends Task{
    public $plugin;
    public function __construct($plugin ){
        $this->plugin = $plugin;
    }
    public function getOwner(){
        return $this->plugin;
    }
    public function onRun($currentTick){
        foreach( $this->getOwner()->getServer()->getOnlinePlayers() as $player ) {
            if ($this->getOwner()->damage($player->getFloorX() ,$player->getFloorZ() , $player) == "true"){
                $player->setHealth($player->getHealth() - 3);
                $player->sendMessage("§b[§f국가전쟁§b]§f 우가우가 야만인이다 나는!! 죽창을 받아라");
            }
        }
    }
}
class money extends Task{
    public $plugin;
    public function __construct($plugin ){
        $this->plugin = $plugin;
    }
    public function getOwner(){
        return $this->plugin;
    }
   public function onRun($currentTick){
       $this->getOwner()->money();
      
   }
}
namespace bingbing;
use onebone\economyapi\EconomyAPI;
use pocketmine\scheduler\Task;
class info extends Task{
    public $plugin;
    public function __construct($plugin ){
        $this->plugin = $plugin;
    }
    public function getOwner(){
        return $this->plugin;
    }
	
	public function onRun(int $currentTick ) {
		
		
		
		foreach( $this->getOwner()->getServer()->getOnlinePlayers() as $player ) {
			$name = $player->getName();
			$소속 = $this->getOwner()->getsosok($player);
			$money = EconomyAPI::getInstance()->myMoney($player);
			
			$sinbon = $this->getOwner()->getsinbon($player);
			if($player->isOp() or !$player->isOp() and $this->getOwner()->getsosok($player) == "무소속"){
				$name = $player->getName();
				$player->sendTip(str_repeat( " ", 53 )."§b["."§f유저 정보"."§b]"."\n"
						.str_repeat( " ", 53 )."§b닉네임: §f".$name."\n"
						.str_repeat( " ", 53 )."§b내돈: §f".$money."§b원 "."  §b신분: §f".$sinbon.
						"\n".str_repeat( " ", 53 )."§bHP: §f".$player->getHealth () . "§b/§f" . $player->getMaxHealth ()  ."§b소속: §f".$소속.
						"\n".str_repeat( " ", 53 )."§b국가 HP: §f무소속");
			}
			else if (!$player->isOp() and $player->isOp() and $this->getOwner()->getsosok($player) !== "무소속"){
				$small = $this->getOwner()->getcountryhealth($player);
				$big = $this->getOwner()->getcountrymaxhealth($player);
				
				$player->sendTip(str_repeat( " ", 53 )."§b["."§f유저 정보"."§b]"."\n"
						.str_repeat( " ", 53 )."§b닉네임: §f".$name."\n"
						.str_repeat( " ", 53 )."§b내돈: §f".$money."§b원 "."  §b신분: §f".$sinbon.
						"\n".str_repeat( " ", 53 )."§bHP: §f".$player->getHealth () . "§b/§f" . $player->getMaxHealth ().str_repeat( " ", 53 )."§b소속: §f".$소속
						.str_repeat( " ", 53 )." §b국가 HP: ".$small."/".$big);
				
			}
		}
	}
}
