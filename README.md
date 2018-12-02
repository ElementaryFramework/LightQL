# LightQL

The lightweight PHP ORM

LightQL is an Object Oriented Mapper (ORM) based on [Query Object](http://martinfowler.com/eaaCatalog/queryObject.html)
and [Data Mapper](https://martinfowler.com/eaaCatalog/dataMapper.html) design patterns.
It uses [Annotations](https://github.com/ElementaryFramework/Annotations) to help you
create, edit, delete, find entities and much more without writing a SQL query.

## Example

### 1. Create a persistence unit

```json
{
  "DBMS": "mysql",
  "Hostname": "127.0.0.1",
  "DatabaseName": "my_app_db",
  "Username": "root",
  "Password": ""
}
```

### 2. Create entities

```php
<?php

namespace MyApp\Entities;

/**
 * Class UserEntity
 *
 * @entity('table' => 'users', 'fetchMode' => \ElementaryFramework\LightQL\Entities\Entity::FETCH_EAGER)
 * @namedQuery('name' => 'findAll', 'query' => 'SELECT * FROM users')
 * @namedQuery('findById', 'SELECT * FROM users u WHERE u.user_id = :id')
 */
class UserEntity extends \ElementaryFramework\LightQL\Entities\Entity
{
    /**
     * @id
     * @autoIncrement
     * @column('name' => 'user_id', 'type' => 'int')
     * @size(11)
     * @notNull
     *
     * @var int
     */
    public $ID = null;

    /**
     * @column('name' => 'first_name', 'type' => 'string')
     * @size(255)
     * @notNull
     *
     * @var string
     */
    public $firstName = null;

    /**
     * @column('name' => 'last_name', 'type' => 'string')
     * @size(255)
     * @notNull
     *
     * @var string
     */
    public $lastName = null;

    /**
     * @column('name' => 'login', 'type' => 'string')
     * @size(15)
     * @notNull
     *
     * @var string
     */
    public $login = null;

    /**
     * @manyToOne(
     *     'entity' => 'TopicEntity',
     *     'column' => 'user_id',
     *     'referencedColumn' => 'author_id'
     * )
     *
     * @var TopicEntity[]
     */
    public $topicEntityCollection;
}
```

```php
<?php

namespace MyApp\Entities;

/**
 * Class TopicEntity
 *
 * @entity('table' => 'topics')
 * @namedQuery('name' => 'findAll', 'query' => 'SELECT * FROM topics')
 * @namedQuery('findById', 'SELECT * FROM topics t WHERE t.topic_id = :id')
 * @namedQuery('findByUser', 'SELECT * FROM topics t WHERE t.author_id = :id')
 */
class TopicEntity extends \ElementaryFramework\LightQL\Entities\Entity
{
    /**
     * @id
     * @autoIncrement
     * @column('name' => 'topic_id', 'type' => 'int')
     * @size(11)
     * @notNull
     *
     * @var int
     */
    public $ID = null;

    /**
     * @column('name' => 'title', 'type' => 'string')
     * @size(255)
     * @notNull
     *
     * @var string
     */
    public $title = null;

    /**
     * @column('name' => 'content', 'type' => 'string')
     * @notNull
     *
     * @var string
     */
    public $text = null;

    /**
     * @oneToMany('entity' => 'UserEntity')
     *
     * @var UserEntity
     */
    public $userEntityReference;
}
```

### 3. Create entity Facades

```php
<?php

namespace MyApp\Sessions;

use MyApp\Entities\UserEntity;

/**
 * Class UserFacade
 */
class UserFacade extends \ElementaryFramework\LightQL\Sessions\Facade
{
    /**
     * @persistenceUnit('myAppPersistenceUnit')
     *
     * @var \ElementaryFramework\LightQL\Entities\EntityManager
     */
    protected $entityManager;

    public function __construct()
    {
        // Constructs the base class with the entity class name managed by this facade
        parent::__construct(UserEntity::class);
    }
}
```

```php
<?php

namespace MyApp\Sessions;

use MyApp\Entities\TopicEntity;

/**
 * Class TopicFacade
 */
class TopicFacade extends \ElementaryFramework\LightQL\Sessions\Facade
{
    /**
     * @persistenceUnit('myAppPersistenceUnit')
     *
     * @var \ElementaryFramework\LightQL\Entities\EntityManager
     */
    protected $entityManager;

    public function __construct()
    {
        // Constructs the base class with the entity class name managed by this facade
        parent::__construct(TopicEntity::class);
    }
}
```

### 4. Play the game !

```php
<?php

namespace MyApp\Controllers;

use ElementaryFramework\LightQL\LightQL;
use ElementaryFramework\LightQL\Persistence\PersistenceUnit;

abstract class BaseController
{
    public function __construct()
    {
        // Register LightQL annotations
        LightQL::registerAnnotations();

        // Register persistence unit
        PersistenceUnit::register("myAppPersistenceUnit", __DIR__ . "/files/persistenceUnit.json");
    }

    public function renderView(string $view, array $data)
    {
        // Your logic to render static views
    }
}
```

```php
<?php

namespace MyApp\Controllers;

use MyApp\Entities\TopicEntity;
use MyApp\Sessions\TopicFacade;

class TopicController extends BaseController
{
    private $_topicFacade;

    public function __construct()
    {
        parent::__construct();

        // Create a new facade
        $this->_topicFacade = new TopicFacade();
    }

    public function newTopic()
    {
        // Create a topic entity from form data
        $topic = new TopicEntity($_POST);
        // Insert the entity into the database
        $this->_topicFacade->create($topic);
    }

    public function editTopic()
    {
        // Get the original topic from the database
        $topic = $this->_topicFacade->find($_POST["topic_id"]);
        // Edit the topic with form data
        $topic->hydrate($_POST);
        // Update the data in the database
        $this->_topicFacade->edit($topic);
    }

    public function getTopics($start = null, $length = null)
    {
        $topics = array();

        if ($start === null && $length === null) {
            $topics = $this->_topicFacade->findAll();
        } else {
            $topics = $this->_topicFacade->findRange($start, $length);
        }

        $this->renderView("topics_page", array("topics" => $topics));
    }

    public function getTopicsOfUser($userId)
    {
        // Get the named query
        $query = $this->_topicFacade->getNamedQuery("findByUser");
        // Set query parameters
        $query->setParam("id", $userId);
        // Execute the query
        $query->run();
        // Retrieve results
        $topics = $query->getResults();

        $this->renderView("topics_page", array("topics" => $topics));
    }

    // etc...
}
```

You can do the same with an `UserController`

## License

&copy; 2018 - Aliens Group

Licensed under MIT ([read license](https://github.com/ElementaryFramework/LightQL/blob/master/LICENSE))
