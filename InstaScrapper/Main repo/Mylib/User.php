<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

 namespace Mylib;

//use Exception;
//use ReflectionObject;

/**
 * Class Entity
 *
 * This is the base class for all entities.
 *
 * @link https://core.telegram.org/bots/api#available-types
 *
 * @method array  getRawData()     Get the raw data passed to this entity
 * @method string getBotUsername() Return the bot name passed to this entity
 */
class User
{
    /**
     * Entity constructor.
     *
     * @todo Get rid of the $bot_username, it shouldn't be here!
     *
     * @param array  $data
     * @param string $bot_username
     *
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function __construct($data)
    {
        //Make sure we're not raw_data inception-ing
        if (isset($data['user'])){
            $user = $data['user'];
            $user['logging_page_id'] = $data['logging_page_id'];
            $user['scanned'] = true;
        } else if(isset($data['users'])){

        }
        //
        if(is_array($user))
            if (array_key_exists('media', $user)) {
                unset($user['media']);
            }
            if (array_key_exists('saved_media', $user)) {
                unset($user['saved_media']);
            }
        }
        //
        $followed_by = $user['followed_by']['count'];
        $follows = $user['follows']['count'];
        $user['followed_by'] = $followed_by;
        $user['follows'] = $follows;
        //
        $this->assignMemberVariables($user);
        $this->validate();
    }

    /**
     * Helper to set member variables
     *
     * @param array $data
     */
    protected function assignMemberVariables(array $data)
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Get the list of the properties that are themselves Entities
     *
     * @return array
     */
    protected function subEntities()
    {
        return [];
    }

    /**
     * Perform any special entity validation
     *
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    protected function validate()
    {
    }

    /**
     * Get a property from the current Entity
     *
     * @param mixed $property
     * @param mixed $default
     *
     * @return mixed
     */
    public function getProperty($property, $default = null)
    {
        if (isset($this->$property)) {
            return $this->$property;
        }

        return $default;
    }

    /**
     * Return the variable for the called getter or magically set properties dynamically.
     *
     * @param $method
     * @param $args
     *
     * @return mixed|null
     */
    public function __call($method, $args)
    {
        //Convert method to snake_case (which is the name of the property)
        $property_name = ltrim(strtolower(preg_replace('/[A-Z]/', '_$0', substr($method, 3))), '_');

        $action = substr($method, 0, 3);
        if ($action === 'get') {
            $property = $this->getProperty($property_name);

            if ($property !== null) {
                //Get all sub-Entities of the current Entity
                $sub_entities = $this->subEntities();

                if (isset($sub_entities[$property_name])) {
                    return new $sub_entities[$property_name]($property, $this->getProperty('bot_username'));
                }

                return $property;
            }
        } elseif ($action === 'set') {
            // Limit setters to specific classes.
            if ($this instanceof InlineEntity || $this instanceof Keyboard || $this instanceof KeyboardButton) {
                $this->$property_name = $args[0];

                return $this;
            }
        }

        return null;
    }

    /**
     * Return an array of nice objects from an array of object arrays
     *
     * This method is used to generate pretty object arrays
     * mainly for PhotoSize and Entities object arrays.
     *
     * @param string $class
     * @param string $property
     *
     * @return array
     */
    protected function makePrettyObjectArray($class, $property)
    {
        $new_objects = [];

        try {
            if ($objects = $this->getProperty($property)) {
                foreach ($objects as $object) {
                    if (!empty($object)) {
                        $new_objects[] = new $class($object);
                    }
                }
            }
        } catch (Exception $e) {
            $new_objects = [];
        }

        return $new_objects;
    }


}
