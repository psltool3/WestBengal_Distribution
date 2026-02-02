<?php

class Depot {
    public $district;
    public $name;
    public $id;
    public $type;
    public $latitude;
    public $longitude;
    public $demand;
    public $demandrice;
    public $uniqueid;
    public $active;

    // Getter methods
    public function getDistrict() {
        return $this->district;
    }

    public function getName() {
        return $this->name;
    }

    public function getId() {
        return $this->id;
    }

    public function getType() {
        return $this->type;
    }

    public function getLatitude() {
        return $this->latitude;
    }

    public function getLongitude() {
        return $this->longitude;
    }

    public function getDemand() {
        return $this->demand;
    }
	
	public function getDemandrice() {
        return $this->demandrice;
    }
	
	public function getUniqueid() {
        return $this->uniqueid;
    }
	
	public function getActive() {
        return $this->active;
    }


    // Setter methods

    public function setDistrict($district) {
        $this->district = $district;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setType($type) {
        $this->type = $type;
    }

    public function setLatitude($latitude) {
        $this->latitude = $latitude;
    }

    public function setLongitude($longitude) {
        $this->longitude = $longitude;
    }

    public function setDemand($demand) {
        $this->demand = $demand;
    }
	
	public function setDemandrice($demandrice) {
        $this->demandrice = $demandrice;
    }
	
	public function setUniqueid($uniqueid) {
        $this->uniqueid = $uniqueid;
    }
	
	public function setActive($active) {
        $this->active = $active;
    }
	
	function insert(Depot $depot){
        return "INSERT INTO depot (district, name, id, type, latitude, longitude, demand,demand_rice, uniqueid, active) VALUES ('".$depot->getDistrict()."','".$depot->getName()."','".$depot->getId()."','".$depot->getType()."','".$depot->getLatitude()."','".$depot->getLongitude()."','".$depot->getDemand()."','".$depot->getDemandrice()."','".$depot->getUniqueid()."','".$depot->getActive()."')";
    }

    function delete(Depot $depot){
        return "DELETE FROM depot WHERE uniqueid='".$depot->getUniqueid()."'";
    }
	
	function deleteall(Depot $depot){
        return "DELETE FROM depot WHERE 1";
    }
	
	function logname(Depot $depot){
		return "SELECT name FROM depot WHERE uniqueid='".$depot->getUniqueid()."'";
    	}

	function check(Depot $depot){
        return "SELECT * FROM depot WHERE uniqueid='".$depot->getUniqueid()."'";
    }
	
	function checkEdit(Depot $depot){
        return "SELECT * FROM depot WHERE LOWER(id)=LOWER('".$depot->getId()."')";
    }
	
	function checkInsert(Depot $depot){
        return "SELECT * FROM depot WHERE LOWER(id)=LOWER('".$depot->getId()."')";
    }

    function update(Depot $depot){
     return  "UPDATE depot SET district = '".$depot->getDistrict()."',name = '".$depot->getName()."',id = '".$depot->getId()."',type = '".$depot->getType()."',latitude = '".$depot->getLatitude()."',longitude = '".$depot->getLongitude()."',demand = '".$depot->getDemand()."',demand_rice = '".$depot->getDemandrice()."' WHERE uniqueid = '".$depot->getUniqueid()."'";
    }
	
	function updateEdit(Depot $depot){
      return  "UPDATE depot SET district = '".$depot->getDistrict()."',name = '".$depot->getName()."',id = '".$depot->getId()."',type = '".$depot->getType()."',latitude = '".$depot->getLatitude()."',longitude = '".$depot->getLongitude()."',demand = '".$depot->getDemand()."',demand_rice = '".$depot->getDemandrice()."' WHERE id = '".$depot->getId()."'";
    }
}  

?>