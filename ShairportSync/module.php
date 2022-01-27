<?
    // Klassendefinition
    class ShairportSync extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->ConnectParent("{1B0A36F7-343F-42F3-8181-0748819FB324}");
            	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("Model", 7);
		$this->RegisterPropertyInteger("Output", 1);
		$this->RegisterPropertyInteger("Address", 0);
		$this->RegisterPropertyInteger("Bit", 0);
		$this->RegisterPropertyInteger("Switchtime", 20);
		$this->RegisterPropertyInteger("Timer_1", 250);
		$this->RegisterTimer("Timer_1", 0, 'I2LFilterPumpe_GetState($_IPS["TARGET"]);');
		
		//Status-Variablen anlegen
		$this->RegisterVariableBoolean("State", "State", "~Switch", 10);
		$this->EnableAction("State");
		$this->RegisterVariableBoolean("Automatic", "Automatik", "~Switch", 20);
		$this->EnableAction("Automatic");
		
		// Anlegen des Wochenplans
		$this->RegisterEvent("Tagesplan", "I2LFilterPumpe_Event_".$this->InstanceID, 2, $this->InstanceID, 50);
		
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
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "LOGO 7", "value" => 7);
		$arrayOptions[] = array("label" => "LOGO 8", "value" => 8);
		$arrayElements[] = array("type" => "Select", "name" => "Model", "caption" => "Modell", "options" => $arrayOptions, "onChange" => 'IPS_RequestAction($id,"RefreshProfileForm",$Model);' );

 		$arrayElements[] = array("type" => "Label", "caption" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "caption" => "Auswahl des Netzwerkeingangs");
		
		$arrayOptions = array();
		for ($i = 0; $i <= 7; $i++) {
			$arrayOptions[] = array("label" => $i, "value" => $i);
		}
		
		$ArrayRowLayout = array();
		$ArrayRowLayout[] = array("type" => "Select", "name" => "Address", "caption" => "Adresse", "options" => $arrayOptions );
		$ArrayRowLayout[] = array("type" => "Select", "name" => "Bit", "caption" => "Bit", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "RowLayout", "items" => $ArrayRowLayout);
		
		$arrayElements[] = array("type" => "Label", "caption" => "Laufzeit des Tast-Impulses");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Switchtime", "caption" => "ms", "minumum" => 0);
		$arrayElements[] = array("type" => "Label", "caption" => "_____________________________________________________________________________________________________"); 
		$arrayElements[] = array("type" => "Label", "caption" => "Auswahl des digitalen Ausgangs oder Merkers"); 
		
		$arrayOptions = array();
		If ($this->ReadPropertyInteger("Model") == 7) {
			for ($i = 1; $i <= 16; $i++) {
				$arrayOptions[] = array("label" => "Q".$i, "value" => $i);
			}
			for ($i = 1; $i <= 27; $i++) {
				$arrayOptions[] = array("label" => "M".$i, "value" => ($i + 100));
			}
		}
		else If ($this->ReadPropertyInteger("Model") == 8) {
			for ($i = 1; $i <= 20; $i++) {
				$arrayOptions[] = array("label" => "Q".$i, "value" => $i);
			}
			for ($i = 1; $i <= 64; $i++) {
				$arrayOptions[] = array("label" => "M".$i, "value" => ($i + 100));
			}
		}
		
		$arrayElements[] = array("type" => "Select", "name" => "Output", "caption" => "Ausgang", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Timer_1", "caption" => "ms", "minumum" => 0);
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
		
		// Anlegen der Daten für den Wochenplan
		IPS_SetEventScheduleGroup($this->GetIDForIdent("I2LFilterPumpe_Event_".$this->InstanceID), 0, 127);
		
		//Anlegen von Aktionen 
		IPS_SetEventScheduleAction($this->GetIDForIdent("I2LFilterPumpe_Event_".$this->InstanceID), 0, "Aus", 0xFF0000, "I2LFilterPumpe_Automatic(\$_IPS['TARGET'], false);");
		IPS_SetEventScheduleAction($this->GetIDForIdent("I2LFilterPumpe_Event_".$this->InstanceID), 1, "Ein", 0x0000FF, "I2LFilterPumpe_Automatic(\$_IPS['TARGET'], true);");

		If (GetValueBoolean($this->GetIDForIdent("Automatic")) == true) {
			$this->DisableAction("State");
		}
		else {
			$this->EnableAction("State");
		}
		
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->GetState();
			If ($this->GetStatus() <> 102) {
				$this->SetStatus(102);
			}
			$this->SetTimerInterval("Timer_1", $this->ReadPropertyInteger("Timer_1") );
		}
		else {
			If ($this->GetStatus() <> 104) {
				$this->SetStatus(104);
			}
			$this->SetTimerInterval("Timer_1", 0);
		}
		
	}
	
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
	        case "State":
			If ($Value <> $this->GetValue("State")) {
				$this->KeyPress($Value);
			}
	            	break;
		case "Automatic":
			If ($Value <> $this->GetValue("Automatic")) {
				$this->SetValue("Automatic", $Value);
				 If ($Value == true) {
				   	   $this->SendDebug("RequestAction", "State wird Disabled", 0);
					   $this->DisableAction("State");
				   }
				   else {
					   $this->SendDebug("RequestAction", "State wird Enabled", 0);
					   $this->EnableAction("State");
				   }
			}
	            	break;
		case "RefreshProfileForm":
				$this->SendDebug("RequestAction", "Wert: ".$Value, 0);
				$this->RefreshProfileForm($Value);
			break;
	        default:
	            throw new Exception("Invalid Ident");
	    	}
	}
	    
	// Beginn der Funktionen
	private function SetState(Bool $State)
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->HasActiveParent() == true)) {
			$this->SendDebug("SetState", "Ausfuehrung", 0);
			$Area = 132; // Konstante
			$Address = $this->ReadPropertyInteger("Address");
			$Bit = $this->ReadPropertyInteger("Bit");
			
			$AddressBit = ($Address * 8) + $Bit;
			$AddressBit = intval($AddressBit);
			
			If ($State == true) {
				$DataPayload = utf8_encode(chr(1));
			}
			else {
				$DataPayload = utf8_encode(chr(0));
			}
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{042EF3A2-ECF4-404B-9FA2-42BA032F4A56}", "Function" => 5, "Area" => $Area, "AreaAddress" => 0, "BitAddress" => $AddressBit, "WordLength" => 1,"DataCount" => 1,"DataPayload" => $DataPayload)));
			//$this->SendDebug("SetState", "Ergebnis: ".intval($Result), 0);
			$this->GetState();
		}
	}
	    
	public function GetState()
	{
		$State = false;
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->HasActiveParent() == true) AND (IPS_GetKernelRunlevel() == KR_READY)) {
			//$this->SendDebug("GetState", "Ausfuehrung", 0);
			$Output = $this->ReadPropertyInteger("Output");
			$AreaAddress = 0;
			
			If ($Output < 100) {
				$Area = 130; // Ausgang
				$BitAddress = $Output - 1;
			}
			else {
				$Area = 131; // Merker
				$BitAddress = $Output - 101;
			}
				
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{042EF3A2-ECF4-404B-9FA2-42BA032F4A56}", "Function" => 4, "Area" => $Area, "AreaAddress" => $AreaAddress, "BitAddress" => $BitAddress, "WordLength" => 1, "DataCount" => 1,"DataPayload" => "")));
			If ($Result === false) {
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
				$this->SendDebug("GetState", "Fehler bei der Ausführung!", 0);
			}
			else {
				If ($this->GetStatus() <> 102) {
					$this->SetStatus(102);
				}
				$State = ord($Result);
				//$this->SendDebug("GetState", "Ergebnis: ".$State, 0);
				If ($State <> $this->GetValue("State")) {
					$this->SetValue("State", $State);
				}
			}
		}
	return $State;
	}
	      
	private function GetInputState()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->HasActiveParent() == true) AND (IPS_GetKernelRunlevel() == KR_READY)) {
			//$this->SendDebug("GetInputState", "Ausfuehrung", 0);
			$Input= $this->ReadPropertyInteger("Input");
			$AreaAddress = 0;
			$Area = 132; // Ausgang
			$BitAddress = $Input + 7383;
				
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{042EF3A2-ECF4-404B-9FA2-42BA032F4A56}", "Function" => 4, "Area" => $Area, "AreaAddress" => $AreaAddress, "BitAddress" => $BitAddress, "WordLength" => 1, "DataCount" => 1,"DataPayload" => "")));
			If ($Result === false) {
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
				$this->SendDebug("GetInputState", "Fehler bei der Ausführung!", 0);
			}
			else {
				If ($this->GetStatus() <> 102) {
					$this->SetStatus(102);
				}
				$State = ord($Result);
				//$this->SendDebug("GetInputState", "Ergebnis: ".$State, 0);
				If ($State <> $this->GetValue("InputState")) {
					$this->SetValue("InputState", $State);
					If ($State == true) {
						$this->SetTimerInterval("Timer_2", $this->ReadPropertyInteger("Timer_2") * 1000);
					}
					elseif ($State == false) {
						$this->SetTimerInterval("Timer_2", 0);
						$this->SetValue("InputLongpress", false);
					}
				}
				
				
			}
		}
	}    
	 
	public function Keypress(bool $State)
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->HasActiveParent() == true)) {	
			$Switchtime = $this->ReadPropertyInteger("Switchtime"); // Dauer der Betätigung
			If ($State <> $this->GetValue("State")) {
				$this->SetState(true);
				IPS_Sleep($Switchtime);
				$this->SetState(false);
			}
		}
  	}
	    
	public function Automatic(bool $State)
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->HasActiveParent() == true)) {	
			$Automatic = $this->GetValue("Automatic");
			$Switchtime = $this->ReadPropertyInteger("Switchtime"); // Dauer der Betätigung
			If (($Automatic == true) AND ($State <> $this->GetValue("State"))) {
				$this->SetState(true);
				IPS_Sleep($Switchtime);
				$this->SetState(false);
			}
		}
  	}
	
	private function RefreshProfileForm($Model)
    	{
        	$arrayOptions = array();
		If ($Model == 7) {
			for ($i = 1; $i <= 16; $i++) {
				$arrayOptions[] = array("label" => "Q".$i, "value" => $i);
			}
			for ($i = 1; $i <= 27; $i++) {
				$arrayOptions[] = array("label" => "M".$i, "value" => ($i + 100));
			}
		}
		else If ($Model == 8) {
			for ($i = 1; $i <= 20; $i++) {
				$arrayOptions[] = array("label" => "Q".$i, "value" => $i);
			}
			for ($i = 1; $i <= 64; $i++) {
				$arrayOptions[] = array("label" => "M".$i, "value" => ($i + 100));
			}
		}
        	$this->UpdateFormField('Output', 'options', json_encode($arrayOptions));
		
		$arrayOptions = array();
		If ($Model == 7) {
			for ($i = 1; $i <= 20; $i++) {
				$arrayOptions[] = array("label" => "I".$i, "value" => $i);
			}
		}
		else If ($Model == 8) {
			for ($i = 1; $i <= 24; $i++) {
				$arrayOptions[] = array("label" => "I".$i, "value" => $i);
			}
		}
		$this->UpdateFormField('Input', 'options', json_encode($arrayOptions));
    	}    
	    
	private function GetParentID()
	{
		$ParentID = (IPS_GetInstance($this->InstanceID)['ConnectionID']);  
	return $ParentID;
	}
  	
  	private function GetParentStatus()
	{
		$Status = (IPS_GetInstance($this->GetParentID())['InstanceStatus']);  
	return $Status;
	}
	    
	private function RegisterEvent($Name, $Ident, $Typ, $Parent, $Position)
	{
		$eid = @$this->GetIDForIdent($Ident);
		if($eid === false) {
		    	$eid = 0;
		} elseif(IPS_GetEvent($eid)['EventType'] <> $Typ) {
		    	IPS_DeleteEvent($eid);
		    	$eid = 0;
		}
		//we need to create one
		if ($eid == 0) {
			$EventID = IPS_CreateEvent($Typ);
		    	IPS_SetParent($EventID, $Parent);
		    	IPS_SetIdent($EventID, $Ident);
		    	IPS_SetName($EventID, $Name);
		    	IPS_SetPosition($EventID, $Position);
		    	IPS_SetEventActive($EventID, true);  
		}
	}  
}
?>
