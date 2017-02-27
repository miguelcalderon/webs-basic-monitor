<?php
class bd {
	/**
	 * Identificador del objeto (la conexión con la base de datos MySQL).
	 */
	public $id;
	/**
	 * Mensaje de error.
	 */
	public $msg;
	/**
	 * Constructor de la clase; se conecta con la base de datos con los datos asignados a las variables correspondientes.
	 *
	 * @return int
	 *   Identificador del objeto (la conexión con la base de datos MySQL).
	 */
	public function __construct($dbs, $usr, $pwd) {
		$params = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
		try {
			$cn = new PDO("mysql:dbname=".$dbs.";host=localhost", $usr, $pwd, $params);
      $cn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->id = $cn;
		} catch(PDOException $e) {
			$this->msg = "Error: no ha sido posible conectar con la base de datos\n" . $e->getMessage();
		}
	}
	/**
	 * Comprueba si existe la tabla y, si no existe, la crea
	 *
	 * @param string $table
	 *   Nombre de la tabla.
	 *
	 * @param string $table_parameters
	 *   Parámetros de creación de la tabla.
	 *
	 * @return bool
	 *   TRUE si la tabla existe o ha podido crearse, FALSE si no.
	 */
	public function checkTable($table, $table_parameters) {
		$cn = $this->id;
		$consulta = $cn->prepare("CREATE TABLE IF NOT EXISTS ".$table." (".$table_parameters.")");
		try {
			$res = $consulta->execute();
			return $res;
		} catch(PDOException $e) {
			$this->msg = "Error: no ha sido posible comprobar la tabla\n" . $e->getMessage();
			return false;
		}
	}
	/**
	 * Ejecuta la instrucción de MySQL
	 *
	 * @param string $query
	 *   Instrucción.
	 *
	 * @return bool
	 *   TRUE si la ejecución ha funcionado, FALSE si no.
	 */
	public function executeDB($query) {
		$cn = $this->id;
		$consulta = $cn->prepare($query);
		$res = $consulta->execute();
		if($res) {
			return true;
		}
		else {
			return false;
		}
	}
	/**
	 * Recupera el dato único de una celda de la fila en la que una columna da un valor determinado.
	 *
	 * @param string $tabla
	 *   Tabla en la que hacer la búsqueda.
	 *
	 * @param string $valor
	 *   Columna de la que recuperar el valor.
	 *
	 * @param string $columna
	 *   Columna cuyo valor hay que comparar.
	 *
	 * @param string $busqueda
	 *   Valor con el que comparar el valor de $columna.
	 *
	 * @return int|bool
	 *   Valor solicitado si funciona, FALSE si no.
	 */
	public function get($tabla, $valor, $columna, $busqueda) {
		$cn = $this->id;
		$consulta = $cn->prepare("SELECT * FROM ".$tabla." WHERE ".$columna."= :".$columna);
		$consulta->bindParam(":".$columna, $busqueda);
		$consulta->execute();
		if($fila=$consulta->fetch()) {
			return $fila[$valor];
		}
		else {
			return false;
		}
	}
	/**
	 * Recupera la matriz de filas que cumplen la condición de tener el valor proporcionado en una columna determinada.
	 *
	 * @param string $tabla
	 *   Tabla en la que hacer la búsqueda.
	 *
	 * @param string $columna
	 *   Columna cuyo valor hay que comparar.
	 *
	 * @param string $busqueda
	 *   Valor con el que comparar el valor de $columna.
	 *
	 * @return array|bool
	 *   Matriz de filas solicitadas si funciona, FALSE si no.
	 */
	public function getArray($tabla, $columna = "", $busqueda = "") {
		$cn = $this->id;
		if(strlen(trim($columna))>0) {
			$consulta = $cn->prepare("SELECT * FROM ".$tabla." WHERE ".$columna."= :".$columna);
		} else {
			$consulta = $cn->prepare("SELECT * FROM ".$tabla);
		}
		$consulta->bindParam(":".$columna, $busqueda);
		try {
			$consulta->execute();
		} catch(PDOException $e) {
			$this->msg = "Error: no ha sido posible recuperar los datos\n" . $e->getMessage();
			return false;
		}
		return $consulta->fetchAll();
	}
	/**
	 * Recupera la matriz de filas que cumplen la condición de tener el valor proporcionado en una columna determinada.
	 *
	 * @param string $tabla
	 *   Tabla en la que hacer la búsqueda.
	 *
	 * @param array $condiciones
	 *   Matriz de pares de clave y valor que estipulan condiciones de identidad para la búsqueda.
	 *
	 * @param string $orden
	 *   Columna que ha de regir el orden de la matriz solicitada.
	 *
	 * @param string $direccion
	 *   Dirección del orden solicitado.
	 *
	 * @return array|bool
	 *   Matriz de filas solicitadas si funciona, FALSE si no.
	 */
	public function getSelectedArray($tabla, $condiciones = array(), $orden = "", $direccion = "ASC", $limit = "") {
		$cn = $this->id;
		$p = "";
		$sql = "SELECT * FROM ".$tabla;
		if (count($condiciones) > 0) {
			$sql.= " WHERE ";
			foreach($condiciones as $clave => $valor) {
				$sql.= $p.$clave."= :".$clave;
				$p = " AND ";
			}
		}
		if(strlen($orden)>0) {
			$sql.= " ORDER BY ".$orden." ".$direccion;
		}
		if(strlen($limit)>0) {
			$sql.= " LIMIT ".$limit;
		}
		$consulta = $cn->prepare($sql);
		$consulta->execute($condiciones);
		if($matriz=$consulta->fetchAll()) {
			return $matriz;
		}
		else {
			return false;
		}
	}
	/**
	 * Recupera una fila de una tabla.
	 *
	 * @param string $tabla
	 *   Tabla en la que hacer la búsqueda.
	 *
	 * @param int $id
	 *   ID de la fila buscada.
	 *
	 * @return array|bool
	 *   Fila solicitada si funciona, FALSE si no.
	 */
	public function getRow($tabla, $id) {
		$cn = $this->id;
		$consulta = $cn->prepare("SELECT * FROM ".$tabla." WHERE id=:id");
		$consulta->bindParam(":id", $id);
		$consulta->execute();
		if($fila=$consulta->fetch()) {
			return $fila;
		}
		else {
			return false;
		}
	}
	/**
	 * Inserta una fila con datos proporcionados por un array asociativo en las columnas con nombre indicado por las claves del array.
	 *
	 * @param string $tabla
	 *   Tabla en la que hacer la inserción.
	 *
	 * @param array $datos
	 *   Array asociativo cuyas claves coinciden con las columnas en las que insertar los valores.
	 *
	 * @return bool
	 *   ID de la fila insertada, FALSE si no.
	 */
	public function insert($tabla, $datos) {
		$cn = $this->id;
		$sql = "INSERT INTO ".$tabla." (";
		$c = "";
		foreach($datos as $clave => $dato) {
			$sql.= $c.$clave;
			$c = ", ";
		}
		$sql.= ") VALUES (";
		$c = "";
		foreach($datos as $clave => $dato) {
			$sql.= $c.":".$clave;
			$c = ", ";
		}
		$sql.= ")";
		$consulta = $cn->prepare($sql);
		try {
			$consulta->execute($datos);
		} catch (PDOException $e) {
			$this->msg = "Error: no ha sido posible insertar el registro\n" . $e->getMessage();
			return false;
		}
		return $cn->lastInsertId();
	}
	/**
	 * Inserta una fila con datos proporcionados por un array asociativo en las columnas con nombre indicado por las claves del array y aplica a esos datos la operación indicada.
	 *
	 * @param string $tabla
	 *   Tabla en la que hacer la inserción.
	 *
	 * @param array $datos
	 *   Array asociativo cuyas claves coinciden con las columnas en las que insertar los valores.
	 *
	 * @param string $op
	 *   Operación que debe incluirse para cada dato suministrado.
	 *
	 * @param array $values
	 *   Array de valores a los que debe aplicarse la operación.
	 *
	 * @return bool
	 *   ID de la fila insertada, FALSE si no.
	 */
	public function insertEx($tabla, $datos, $op = "", $values = array()) {
		$cn = $this->id;
		$sql = "INSERT INTO ".$tabla." (";
		$c = "";
		foreach($datos as $clave => $dato) {
			$sql.= $c.$clave;
			$c = ", ";
		}
		$sql.= ") VALUES (";
		$c = "";
		foreach($datos as $clave => $dato) {
			if(strlen($op)>0) {
				if(in_array($clave, $values)) {
					$sql.= $c.$op."(:".$clave.")";
					$c = ", ";
				}
				else {
					$sql.= $c.":".$clave;
					$c = ", ";
				}
			}
			else {
				$sql.= $c.":".$clave;
				$c = ", ";
			}
		}
		$sql.= ")";
		$consulta = $cn->prepare($sql);
		try {
			$consulta->execute($datos);
		} catch (PDOException $e) {
			return false;
		}
		return $cn->lastInsertId();
	}
	/**
	 * Cambia los datos de la fila indicada de una tabla según los datos proporcionados por un array asociativo en las columnas con nombre indicado por las claves del array.
	 *
	 * @param string $tabla
	 *   Tabla que se va a modificar.
	 *
	 * @param array $datos
	 *   Datos que se van a modificar.
	 *
	 * @param int $id
	 *   ID de la fila que se va a modificar.
	 *
	 * @return bool
	 *   TRUE si funciona, FALSE si no.
	 */
	public function update($tabla, $datos, $id) {
		if(count($datos)>0) {
			$cn = $this->id;
			$sql = "UPDATE ".$tabla." SET ";
			$c = "";
			foreach($datos as $clave => $dato) {
				$sql.= $c."`".$clave."`=?";
				$c = ", ";
			}
			$sql.= " WHERE id=".$id;
			$consulta = $cn->prepare($sql);
			return $consulta->execute(array_values($datos));
		}
		else {
			return false;
		}
	}
	/**
	 * Elimina la fila indicada de una tabla según los datos proporcionados por un array asociativo en las columnas con nombre indicado por las claves del array.
	 *
	 * @param string $tabla
	 *   Tabla que se va a modificar.
	 *
	 * @param array $datos
	 *   Datos que se van a modificar.
	 *
	 * @return bool
	 *   TRUE si funciona, FALSE si no.
	 */
	public function delete($tabla, $datos) {
		$cn = $this->id;
		$sql = "DELETE FROM ".$tabla." WHERE ";
		$c = "";
		foreach($datos as $clave => $dato) {
			$sql.= $c.$clave."= :".$clave;
			$c = " AND ";
		}
		$consulta = $cn->prepare($sql);
		try {
			$res = $consulta->execute($datos);
			return $res;
		} catch (PDOException $e) {
			$this->msg = "Error: no ha sido posible norrar el registro\n" . $e->getMessage();
			return false;
		}
	}
	/**
	 * Hacer consulta SQL.
	 *
	 * @param string $sql
	 *   Consulta SQL.
	 *
	 * @return bool|array
	 *   Matriz de resultados si funciona, FALSE si no.
	 */
	public function query($sql) {
		$cn = $this->id;
		$consulta = $cn->prepare($sql);
		$res = $consulta->execute();
		if($res) {
			//return $res;
			return $consulta->fetchAll();
		}
		else {
			return false;
		}
	}
	/**
	 * Recupera el identificador del objeto (la conexión con la base de datos MySQL).
	 *
	 * @return int
	 *   Identificador del objeto (la conexión con la base de datos MySQL).
	 */
	public function id() {
		return $this->id;
	}
}