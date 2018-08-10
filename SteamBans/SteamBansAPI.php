<?php
/**
 * Класс для работы с API SteamBans
 * @author Jon4ik for Mysteam.ru
 * @url https://mysteam.ru/help/api/
 * @version 1.4.1
 */
class SteamBansAPI 
{
	private $key     = null; # Ключ доступа к API 
	private $method  = null; # Метод использования API(подробнее о методах mysteam.ru/help/api)
	private $url     = null; # Готовая ссылка с параметрами API
	private $info    = null; # array с данными о бане
	private $logfife = ''; 	 # Файл с логом
	
	# Получаем данные и подключаем их

	public function __construct($key = null, $method = "onlycheck", $logfile = null)
	{
		$this->key = $key;
		$this->method = $method;
		$this->logfife = $logfile;
		
		if($this->key == null)
		{
			$this->errors("[Settigs] Нет API ключа.");
		}
		
		if($this->IsValudeMethod() == 0)
		{
			$this->errors("[Settings] Неизвестный метод получения данных.");
		}
				
		$this->url = "http://api.mysteam.ru/steambans/{$this->method}.php?key={$this->key}";
	}
	
	# Функция вывода ошибок.
	
	public function errors($error)
	{
		if($this->logfife != null)
		{
			echo $this->logs($error);
		}
		return die($error);
	}
	
	# Проверка на валидность метода
	
	private function IsValudeMethod()
	{
		return ($this->method == "onlycheck" || $this->method == "fullcheck") ? 1 : 0;
	}
	
	# функция записи сообщений в лог.
	 
	private function logs($error)
	{
		$handle = @fopen("{$this->logfife}", 'a');
		@fwrite($handle, "[".date('d.m.Y - H:i')."] - {$error}\r\n");
		@fclose($handle);
	}
	
	# Получение информации о бане.

	public function checkban($steamid = null)
	{
		if($steamid == null)
		{
			$this->errors("[checkban] Нет Steamid");
		}
		
		if($curl = curl_init())
		{	
			 curl_setopt($curl, CURLOPT_URL, "{$this->url}&st={$steamid}");
			 curl_setopt($curl, CURLOPT_RETURNTRANSFER,	true);
			 curl_setopt($curl, CURLOPT_NOSIGNAL, 1); 
			 curl_setopt($curl, CURLOPT_TIMEOUT_MS, 200);		 
			 $this->info = curl_exec($curl);
			 		 
			 if (curl_errno($curl) || curl_getinfo($curl, CURLINFO_HTTP_CODE) != 200) 
			 {			
				 $this->errors("База SteamBans в данный момент недоступна. Код ошибки: " . curl_errno($curl));
			 }
			 
			 $this->info = json_decode($this->info, true);
			 curl_close($curl);
		}
		else
		{
			$this->errors("[checkban] Ошибка! Не удалось инициализировать CURL");
		}
		
		return $this->info;
	}
	
	# Получение информации о голосование за заявку.
	
	public function InfoVotes()
	{
		if(!$this->info)
		{
			$this->errors("[InfoVotes] Нет информации о заявке");
		}
					
		$yes = $this->info['VotesYES']; # Голосов За
		$no = $this->info['VotesNO'];   # Голосов Против
		$ns = $this->info['VotesNS'];   # Голосов за Нет статуса
		$votes = $yes + $no + $ns;		# Всего голосов
				
		$infovotes =  array();
		$infovotes['yes']    = $yes; # Голосов За
		$infovotes['no']     = $no;  # Голосов Против
		$infovotes['ns']     = $ns;  # Голосов за Нет статуса
		$infovotes['votes']  = $votes; # Всего голосов
		if($votes)
		{
			$infovotes['yespercentage'] = round(100 * ($yes/$votes));  # Процент голосов за
			$infovotes['nopercentage']  = round(100 * ($no/$votes));   # Процент голосов Против
			$infovotes['nspercentage']  = round(100 * ($ns/$votes));   # Процент голосов за Нет статуса
		}
		
		return $infovotes;
	}
	
	# Получение информации о времени добавления заявки в базу SteamBans (В "Человеческом" виде).
	
	public function TimeAddInfo()
	{
		if(!$this->info)
		{
			$this->errors("[TimeAddInfo] Нет информации о заявке");
		}
			
		return  date("d.m.Y В H:i:s", $this->info[TimeSub]);
	}
	
	# Получение информации о времени бане игрока (В "Человеческом" виде).
	
	public function TimeBanInfo()
	{
		if(!$this->info)
		{
			$this->errors("[TimeBanInfo] Нет информации о заявке");
		}
					
		return date("d.m.Y В H:i:s", $this->info[TimeBan]);
	}
	
	# Получение причины бана
	
	public function reason($reasonid = null)
	{
		if(!$this->info)
		{
			$this->errors("[Reason] Нет информации о заявке");
		}
		
		switch($this->info[GamerCheat])
		{
			case 1:
			{
				$reason = "Wallhack";
				break;
			}
			case 2:
			{
				$reason = "AimBot";
				break;
			}
			case 3:
			{
				$reason = "Antiflash";
				break;
			}
			case 4:
			{
				$reason = "AntiRecoil";
				break;
			}
			case 5:
			{
				$reason = "NoSmoke";
				break;
			}
			case 6:
			{
				$reason = "SpeedHack";
				break;
			}
			case 7:
			{
				$reason = "Multi-Hack";
				break;
			}
		}
		
		return $reason;
	}
	
	# Получение статуса заявки.
	
	public function status($statusid = null)
	{
		if(!$this->info)
		{
			$this->errors("[Status] Нет информации о заявке");
		}
		
		switch($this->info[BanStatus])
		{		
			case 1:
			{
				$status = "Новая заявка";
				break;
			}
			case 2:
			{
				$status = "Отклоненная заявка";
				break;
			}
			case 3:
			{
				$status = "Активный бан";
				break;
			}
			case 4:
			{
				$status = "Бан снят";
				break;
			}
		}
		
		return $status;
	}
	
	# Получение название игры из заявки(В какой игре забанен пользователь).
	
	public function game()
	{
		if(!$this->info)
		{
			$this->errors("[Game] Нет информации о заявке");
		}
		
		switch($this->info[GameID])
		{			
			case 1:
			{
				$game = "Counter-Strike: Global Offensive";
				break;
			}
			case 2:
			{
				$game = "Counter-Strike: Source";
				break;
			}
		}
		
		return $game;
	}
}