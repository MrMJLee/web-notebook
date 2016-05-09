<?php
class Note
{
    /**
     * @var integer the note ID
     */
    public $id;
    
    /**
     * @var string the note topic
     */
    public $topic;
    
    /**
     * @var string body text of note
     */
    public $outline;
    /**
     * @var integer last updated UNIX timestamp
     */
    public $last_updated;
    
    /**
     * @return string the database file
     */
    public static function dbFile()
    {
        return 'db/notebook.db';
    }
    
    /**
     * @return string table name
     */
    public static function tableName()
    {
        return 'note';
    }
    
    /**
     * @return SQLite3 db
     */
    public static function getDb()
    {	if (!file_exists('db')) {
    		mkdir('db');
		}
		$db = new SQLite3( self::dbFile () );
		if (!$db) die($db->lastErrorMsg());
		
		$createTableStmt = "CREATE TABLE IF NOT EXISTS ". self::tableName().
		"(ID      	 INTEGER PRIMARY KEY,
		topic    	 CHAR(255) NOT NULL,
		outline  	 TEXT NOT NULL,
		last_updated INTEGER NOT NULL
		)";
		
		$ok = $db->exec($createTableStmt);  # create the table
		
		if (!$ok) {
			echo "Couldn't create "+ self::tableName()+"<p>" . $db->lastErrorMsg();
		}
		else 
		{
			return $db;
		}
    }
    
    /**
     * @return string returns the last_updated timestamp in format "May 6, 2016, 9:45pm"
     */
    public function getFormattedDate() 
    {
        return date("g:i a, d/m/Y", $this->last_updated);
    }
    
    /**
     *
     * @return boolean whether this is a new record
     */
    protected function isNew()
    {
        if (isset ( $this->id ) && self::find ( $this->id )) {
            return false;
        }
        return true;
    }
    
    /**
     * @return boolean whether save was successful
     */
    public function save()
    {
        $this->last_updated = time ();
        // save record
        // if new record call add otherwise update
        if ($this->isNew ()) {
            return $this->insert();
        }
        return $this->update();
    }
    
    /**
     * @param unknown $id           
     * @throws Exception
     * @return Note|boolean returns a note
     *         if note with id found or false if not found
     */
    public static function find($id)
    {
        $tableName = self::tableName ();
        $db = self::getDb();
        $stmt = $db->prepare("select * from $tableName where id=:id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $results = $stmt->execute();
        
        if (! $results) {
            throw new Exception ( 'No results' );
        }
        if ($row = $results->fetchArray ()) {
            $note = new Note ();
            $note->id = $row ['ID'];
            $note->outline = $row ['outline'];
            $note->topic = $row ['topic'];
            $note->last_updated = $row ['last_updated'];
            $stmt->close();
            return $note;
        }
        return false;
    }
    
    /**
     * @return boolean whether delete successful
     */
    public function delete()
    {
        $tableName = self::tableName ();
        $id = $this->id;
        $db = self::getDb();
        
        $stmt = $db->prepare("delete from $tableName where id=:id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        
        $deleted = $stmt->execute();
        $stmt->close();
        return $deleted;
    }
    
    /**
     * @return boolean whether record has successfully been inserted
     */
    protected function insert()
    {
        $db = self::getDb ();
        $tableName = self::tableName ();
        
        $topic = $this->topic;
        $outline = $this->outline;
        $last_updated = $this->last_updated;
        
        $stmt = $db->prepare("insert into $tableName (topic, outline, last_updated) 
                    values(:topic,:outline, :updated)");
        $stmt->bindValue(':topic', $topic, SQLITE3_TEXT);
        $stmt->bindValue(':outline', $outline, SQLITE3_TEXT);
        $stmt->bindValue(':updated', $last_updated, SQLITE3_INTEGER);
        
        
        if ($inserted = $stmt->execute()) {
            $this->id = $db->lastInsertRowID ();
        }
        $stmt->close();
        return $inserted;
    }
    
    /**
     * @return boolean whether record has successfully been updated
     */
    protected function update() 
    {
        if (is_null($id = $this->id)) {
            throw new Exception('Can not update: id not set');
        }
        $db = self::getDb ();
        $tableName = self::tableName ();
        // TODO parameterised sql
        $topic = $this->topic;
        $outline = $this->outline;
        $last_updated = $this->last_updated;
        $stmt = $db->prepare("update $tableName set topic=:topic, outline=:outline, last_updated=:updated where id=:id");
        $stmt->bindValue(':topic', $topic, SQLITE3_TEXT);
        $stmt->bindValue(':outline', $outline, SQLITE3_TEXT);
        $stmt->bindValue(':updated', $last_updated, SQLITE3_INTEGER);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $updated = $stmt->execute();
        $stmt->close();
        return $updated;
    }
    
    /**
     * @return Note[] all notes
     */
    public static function findAll()
    {
        $tablename = self::tableName ();
        $query = "select * from $tablename order by topic;";
        $db = self::getDb ();
        $results = $db->query ( $query );
        
        if (! $results) {
            throw new Exception ( 'Query failed.' );
        }
        
        $notes = [ ];
        while ( $row = $results->fetchArray () ) {
            $note = new static();
            $note->id = $row ['ID'];
            $note->outline = $row ['outline'];
            $note->topic = $row ['topic'];
            $note->last_updated = $row ['last_updated'];
            $notes [] = $note;
        }
        $db->close();
        return $notes;
    }

    /**
     * Creates a new Note instance from topic and outline
     * @param string $topic topic name
     * @param string $outline outline text
     * @return Note|boolean returns new note 
     */
    public static function createNewNote($topic, $outline)
    {   
        $note = new static();
        $note->topic = $topic;
        $note->outline = $outline;
        return $note;
    }
	
    public static function search($term)
    {
    	$tableName = self::tableName ();
    	$db = self::getDb();
    	$searchTerm = "%$term%";
    	$stmt = $db->prepare("SELECT * FROM $tableName WHERE topic LIKE :term1 OR outline LIKE :term2 ORDER BY topic");
        $stmt->bindParam(':term1', $searchTerm);
        $stmt->bindParam(':term2', $searchTerm);
        $results = $stmt->execute();
    	if (! $results) {
    		throw new Exception (  );
    	}
    	
        $notes = [];
        while ( $row = $results->fetchArray () ) {
            $note = new static();
            $note->id = $row ['ID'];
            $note->outline = $row ['outline'];
            $note->topic = $row ['topic'];
            $note->last_updated = $row ['last_updated'];
            $notes [] = $note;
        }
        $stmt->close();
        return $notes;
    }
    
}