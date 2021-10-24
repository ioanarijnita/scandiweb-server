
<?php
require('vendor/autoload.php');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE,PATCH,OPTIONS');

$serverName = "localhost";
$username = "root";
$password ="";
$databaseName = "product";

$conn = mysqli_connect("eu-cdbr-west-01.cleardb.com", "b516586a3f87f0", "8422784f", "heroku_510a7e1eae0eabb");

$data = json_decode(file_get_contents('php://input'), true);

$sku = $data['sku'];
$name = $data['name'];
$price = $data['price'];
$type = $data['type'];
$size = $data['size'];
$weight = $data['weight'];
$height = $data['height'];
$width = $data['width'];
$length = $data['length'];

$idProduct = $data['idProduct'];

 abstract class Product {
    protected $sku, $name, $price, $type;
    public function __construct(){
        global $conn;
        $this->conn = $conn;
    }

    public function getSKU (){
        return $this->sku;
    }

    public function setSKU ($sku){
        $this->sku = $sku;
    }
    public function getName (){
        return $this->name;
    }

    public function setName ($name){
        if ((is_string($name) && strlen($name) > 1 ) || is_numeric($name)){
            $this->name = $name;
        }
    }
    public function getPrice (){
        return $this->price;
    }

    public function setPrice ($price){
        if (is_numeric($price)){
            $this->price = $price;
        }
    }

    public function getType (){
        return $this->type;
    }

    public function setType ($type){
        if (is_string($type)){
            $this->type = $type;
        }
    }
    public function insertsingle(){
        $name1 = $this->getName();
        $price1 = $this->getPrice();
        $sku1 =$this->getSKU();
        $type1 = $this->getType();
        $query = "INSERT INTO products (sku, name, price, type) VALUES (?,?,?,?)";
        $stmt = mysqli_prepare($this->conn, $query);
        $stmt->bind_param("ssss", $sku1, $name1, $price1, $type1);
        $rsint = $stmt->execute();
        return $rsint;
    }
  
}

class DVD extends Product{
    private $size;

    public function getSize (){
        return $this->size;
    }

    public function setSize ($size){
        if (is_numeric($size)){
            $this->size = $size;
        }
    }

    public function insertsingle(){
        $size1 = $this->getSize();
        if ($this->size === NULL) {
            return;
        }
        else {
            parent::insertsingle();
            $query = "UPDATE products SET size = (?) WHERE idProduct = (SELECT MAX(idProduct) FROM products)";
            $stmt = mysqli_prepare($this->conn, $query);
            $stmt->bind_param("s", $size1);
            $rsint = $stmt->execute();
            return $rsint;
        }
    }
   
}

class Book extends Product {
   private $weight;

   public function getWeight (){    
    return $this->weight;
   }

    public function setWeight ($weight){
    if (is_numeric($weight)){
        $this->weight = $weight;
    }
}

public function insertsingle(){
    $weight1 = $this->getWeight();
    if ($this->weight === NULL) {
        return;
    } 
    else {
        parent:: insertsingle();
        $query = "UPDATE products SET weight = (?) WHERE idProduct = (SELECT MAX(idProduct) FROM products)";
        $stmt = mysqli_prepare($this->conn, $query);
        $stmt->bind_param("s", $weight1);
        $rsint = $stmt->execute();
        return $rsint;
    }

}

}

class Furniture extends Product{
   private $height, $width, $length;

   public function getHeight () {
    return $this->height;
    }

    public function setHeight ($height){
        if (is_numeric($height)){
            $this->height = $height;
        }
    }

    public function getWidth (){
        return $this->width;
    }

    public function setWidth ($width){
        if (is_numeric($width)){
            $this->width = $width;
        }
    }

    public function getLength (){
        return $this->length;
    }

    public function setLength ($length){
        if (is_numeric($length)){
            $this->length = $length;
        }
    }
    public function insertsingle(){
        $height1 = $this->getHeight();
        $width1 = $this->getWidth();
        $length1 = $this->getLength();
        if ($this->height === NULL) {
            return;
        }
        else {
            parent:: insertsingle();
            $query = "UPDATE products SET height = (?), width = (?), length = (?) WHERE idProduct = (SELECT MAX(idProduct) FROM products)";
            $stmt = mysqli_prepare($this->conn, $query);
            $stmt->bind_param("sss", $height1, $width1, $length1);
            $rsint = $stmt->execute();
            return $rsint;
        }
    }
}

class ProductsList{
private $idProduct;
    public function __construct(){
        global $conn;
        $this->conn = $conn;
    }

    public function setIdProduct($idProduct){
        $this->idProduct = $idProduct;
    }

    public function getIdProduct(){
        return $this->idProduct;
    }

   public function addProductsToList(mysqli_result $products){
        $productsArray = array();
        while($row = mysqli_fetch_assoc($products)) {
            $productsArray[] = $row;
        }
        return json_encode($productsArray);
   }


   public function deleteProduct(){
    $i = 0;
    $idProduct = $this->getIdProduct();
    $arrLength = count($idProduct);
    while ($i < $arrLength) {
        $query = "DELETE FROM  products WHERE idProduct = (?)";
        $stmt = mysqli_prepare($this->conn, $query);
        $stmt->bind_param("s", $idProduct[$i]);
        $rsint = $stmt->execute();
        $i++;
    }
    return $rsint;
   }
}

$dvd = new DVD;
$book = new Book;
$furniture = new Furniture;

$productList = new ProductsList;

    $dvd->setSKU($sku);
    $dvd->setName($name);
    $dvd->setPrice($price);
    $dvd->setType($type);
    $dvd->setSize($size);
    
    $book->setSKU($sku);
    $book->setName($name);
    $book->setPrice($price);
    $book->setType($type);
    $book->setWeight($weight);

    $furniture->setSKU($sku);
    $furniture->setName($name);
    $furniture->setPrice($price);
    $furniture->setType($type);
    $furniture->setHeight($height);
    $furniture->setWidth($width);
    $furniture->setLength($length);

    $productList->setIdProduct($idProduct);

    if (isset($sku)) {
        $book -> insertsingle();
        $dvd -> insertsingle();
        $furniture -> insertsingle();
    } 
    else if (isset($idProduct)) {
        $productList->deleteProduct();
    }
    else {
        $sql = "SELECT MAX(idProduct) FROM products";
        $result = mysqli_query($conn, $sql);
        echo $productList->addProductsToList($result);
    }
?>