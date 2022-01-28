<?
    // Klassendefinition
    class ShairportSync extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->ConnectParent("{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}");
            	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyString("Topic", "Topic");
		
		//Status-Variablen anlegen
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
	    	$PacketType = $Data->PacketType;
		$QualityOfService = $Data->QualityOfService;
		$Retain = $Data->Retain;
		$Topic = utf8_decode($Data->Topic);
		$Payload = utf8_decode($Data->Payload);
		
		$this->SendDebug("ReceiveData", "PacketType: ".$PacketType." QualityOfService: ".$QualityOfService." Retain: ".$Retain." Topic: ".$Topic." Payload: ".$Payload, 0);
		
		$this->ShowMQTTData($PacketType, $QualityOfService, $Retain, $Topic, $Payload);
		
	}
	    
	// Beginn der Funktionen
	private function ShowMQTTData(int $PacketType, int $QualityOfService, Bool $Retain, String $Topic, String $Payload)
	{
		$MainTopic = $this->ReadPropertyString("Topic");
		If ($this->ReadPropertyBoolean("Open") == true) {
			switch($Topic) {
				case $MainTopic."/core/asar": // Artist
					$this->SetValue("Artist"), $Payload);
					break;
				case $MainTopic."/core/asal": // Album
					$this->SetValue("Album"), $Payload);
					break;
				case $MainTopic."/core/minm": // Titel
					$this->SetValue("Title"), $Payload);
					break;
				case $MainTopic."/core/asgn": // Genre
					$this->SetValue("Genre"), $Payload);
					break;
				case $MainTopic."/core/asal": // Songalbum
					$this->SetValue("Songalbum"), $Payload);
					break;
			default:
			    throw new Exception("Invalid Ident");
			}
		}
	}
	 
	
	/*
	asfm -- "format"
	pvol -- "volume"
	clip -- "client_ip"   
	 */   
	    
}
?>
