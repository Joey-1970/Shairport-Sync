<?
    // Klassendefinition
    class ShairportSync extends IPSModule 
    {
	// https://github.com/mikebrady/shairport-sync/blob/master/MQTT.md
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->ConnectParent("{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}");
            	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyString("Topic", "Topic");
		
		// Profile anlegen
		$this->RegisterMediaObject("Cover_".$this->InstanceID, "Cover_".$this->InstanceID, 1, $this->InstanceID, 200, true, "Cover.png");

		
		// Status-Variablen anlegen
		$this->RegisterVariableString("Artist", "Interpret", "", 10);	
		$this->RegisterVariableString("Album", "Album", "", 20);
		$this->RegisterVariableString("Title", "Titel", "", 30);
		$this->RegisterVariableString("Genre", "Genre", "", 40);
		
		$this->RegisterVariableString("Songalbum", "Songalbum", "", 50);
		
        }
       	
	public function GetConfigurationForm() { 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Instanz ist fehlerhaft"); 
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "Kommunikationfehler!");
		
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
		$arrayElements[] = array("name" => "Topic", "type" => "ValidationTextBox",  "caption" => "Topic"); 
		
		
		$arrayElements[] = array("type" => "Label", "caption" => "_____________________________________________________________________________________________________");
		$arrayActions = array(); 
		$arrayActions[] = array("type" => "Label", "label" => "Test Center"); 
		$arrayActions[] = array("type" => "TestCenter", "name" => "TestCenter");
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	} 
	
	// Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
                // Diese Zeile nicht löschen
                parent::ApplyChanges();
		
		$Content = file_get_contents(__DIR__ . '/../imgs/AirPlay.png'); 
		IPS_SetMediaContent($this->GetIDForIdent("Cover_".$this->InstanceID), base64_encode($Content));  //Bild Base64 codieren und ablegen
		IPS_SendMediaEvent($this->GetIDForIdent("Cover_".$this->InstanceID)); //aktualisieren

		
		
		If ($this->ReadPropertyBoolean("Open") == true) {
			
			If ($this->GetStatus() <> 102) {
				$this->SetStatus(102);
			}
			
		}
		else {
			If ($this->GetStatus() <> 104) {
				$this->SetStatus(104);
			}
			
		}
		
	}
	
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
	      
	        default:
	            throw new Exception("Invalid Ident");
	    	}
	}
	    
	public function ReceiveData($JSONString) 
	{
		// Empfangene Daten vom I/O
	    	$Data = json_decode($JSONString);
		if (isset($Data->PacketType)) {
	    		$PacketType = $Data->PacketType;
		} else {
			return;
		}
		$QualityOfService = $Data->QualityOfService;
		$Retain = $Data->Retain;
		$Topic = utf8_decode($Data->Topic);
		$Payload = utf8_decode($Data->Payload);
		
		//$this->SendDebug("ReceiveData", "PacketType: ".$PacketType." QualityOfService: ".$QualityOfService." Retain: ".$Retain." Topic: ".$Topic." Payload: ".$Payload, 0);
		
		$this->ShowMQTTData($PacketType, $QualityOfService, $Retain, $Topic, $Payload);
		
	}
	    
	// Beginn der Funktionen
	private function ShowMQTTData(int $PacketType, int $QualityOfService, Bool $Retain, String $Topic, String $Payload)
	{
		$MainTopic = $this->ReadPropertyString("Topic");
		If ($this->ReadPropertyBoolean("Open") == true) {
			switch($Topic) {
				case $MainTopic."/core/asar": // Artist
					$this->SetValue("Artist", $Payload);
					break;
				case $MainTopic."/core/asal": // Album
					$this->SetValue("Album", $Payload);
					break;
				case $MainTopic."/core/minm": // Titel
					$this->SetValue("Title", $Payload);
					break;
				case $MainTopic."/core/asgn": // Genre
					$this->SetValue("Genre", $Payload);
					break;
				case $MainTopic."/core/asfm": // Format
					
					break;
				case $MainTopic."/core/pvol": // Volume
					
					break;
				case $MainTopic."/core/clip": //Client IP
					
					break;
				case $MainTopic."/core/asal": // Songalbum
					$this->SetValue("Songalbum", $Payload);
					break;	
					
					
				case $MainTopic."/ssnc/PICT": // Cover
					$ImageData = array();
					$ImageData = @getimagesize('data://text/plain;base64' . base64_encode($Payload));
					$this->SendDebug("ShowMQTTData", "Coverformat: ".$ImageData['mime'], 0);
					IPS_SetMediaContent($this->GetIDForIdent("Cover_".$this->InstanceID), base64_encode($Payload));  //Bild Base64 codieren und ablegen
					IPS_SendMediaEvent($this->GetIDForIdent("Cover_".$this->InstanceID)); //aktualisieren
					break;
				case $MainTopic."/ssnc/pbeg": // Play Stream Begin
					$this->SendDebug("ShowMQTTData", "Play Stream Begin", 0);
					break;
				case $MainTopic."/ssnc/pend": // Play Stream End
					$this->SendDebug("ShowMQTTData", "Play Stream End", 0);
					break;
				case $MainTopic."/ssnc/pfls": // Play Stream Flush
					$this->SendDebug("ShowMQTTData", "Play Stream Flush", 0);
					break;
				case $MainTopic."/ssnc/prsm": // Play Stream Resume
					$this->SendDebug("ShowMQTTData", "Play Stream Resume", 0);
					break;
			}
		}
	}
	 
	
	/*
	


p
pvol -- play volume. The volume is sent as a string -- "airplay_volume,volume,lowest_volume,highest_volume", where "volume", "lowest_volume" and "highest_volume" are given in dB. The "airplay_volume" is what's sent by the source (e.g. iTunes) to the player, and is from 0.00 down to -30.00, with -144.00 meaning "mute". This is linear on the volume control slider of iTunes or iOS AirPlay. If the volume setting is being ignored by Shairport Sync itself, the volume, lowest_volume and highest_volume values are zero.
prgr -- progress -- this is metadata from AirPlay consisting of RTP timestamps for the start of the current play sequence, the current play point and the end of the play sequence.
mdst -- a sequence of metadata is about to start. The RTP timestamp associated with the metadata sequence is included as data, if available.
mden -- a sequence of metadata has ended. The RTP timestamp associated with the metadata sequence is included as data, if available.
pcst -- a picture is about to be sent. The RTP timestamp associated with it is included as data, if available.
pcen -- a picture has been sent. The RTP timestamp associated with it is included as data, if available.
snam -- a device e.g. "Joe's iPhone" has started a play session. Specifically, it's the "X-Apple-Client-Name" string.
snua -- a "user agent" e.g. "iTunes/12..." has started a play session. Specifically, it's the "User-Agent" string.
stal -- this is an error message meaning that reception of a large piece of metadata, usually a large picture, has stalled; bad things may happen.



char *commands[] = {"command",    "beginff",       "beginrew",   "mutetoggle", "nextitem",
                      "previtem",   "pause",         "playpause",  "play",       "stop",
                      "playresume", "shuffle_songs", "volumedown", "volumeup",   NULL};
		      
		      
		      
	 */   
	    
	public function SendCommand(String $Command)
	{
		$MainTopic = $this->ReadPropertyString("Topic");
		If ($this->ReadPropertyBoolean("Open") == true) {
			$Response = $this->SendDataToParent(json_encode(Array("DataID"=> "{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}", "PacketType" => 3, "QualityOfService" => 0, "Retain" => false, "Topic" => $MainTopic."/remote".$Command, "Payload" => $Command )));
				
		}
		

			
	}
	private function RegisterMediaObject($Name, $Ident, $Typ, $Parent, $Position, $Cached, $Filename)
	{
		$MediaID = @$this->GetIDForIdent($Ident);
		if($MediaID === false) {
		    	$MediaID = 0;
		}
		
		if ($MediaID == 0) {
			 // Image im MedienPool anlegen
			$MediaID = IPS_CreateMedia($Typ); 
			// Medienobjekt einsortieren unter Kategorie $catid
			IPS_SetParent($MediaID, $Parent);
			IPS_SetIdent($MediaID, $Ident);
			IPS_SetName($MediaID, $Name);
			IPS_SetPosition($MediaID, $Position);
                    	IPS_SetMediaCached($MediaID, $Cached);
			$ImageFile = IPS_GetKernelDir()."media".DIRECTORY_SEPARATOR.$Filename;  // Image-Datei
			IPS_SetMediaFile($MediaID, $ImageFile, false);    // Image im MedienPool mit Image-Datei verbinden
		}  
	}        
}
?>
