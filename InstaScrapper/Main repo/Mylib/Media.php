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
class Media
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
    public function __construct($media)
    {
        //Make sure we're not raw_data inception-ing
        $pos = strpos($media['id'], '_');
        if ($pos){
            $media['scanned'] = 1;
            $media['id'] = substr($media['id'], 0, $pos);
            //
            $media['user_id'] = $media['user']['id'];
            unset($media['user']);
            //
            $media['caption'] = $media['caption']['text'];
            //
            $media['likes'] = $media['likes']['count'];
            $media['comments'] = $media['comments']['count'];
            //
            $media['img_thumbnail'] = $media['images']['thumbnail']['url'];
            $media['img_standard_resolution'] = $media['images']['standard_resolution']['url'];
            $media['img_low_resolution'] = $media['images']['low_resolution']['url'];
            //
            if (isset($media['videos'])){
                $media['vid_low_bandwidth'] = $media['videos']['low_bandwidth']['url'];
                $media['vid_low_resolution'] = $media['videos']['low_resolution']['url'];
                $media['vid_standard_resolution'] = $media['videos']['standard_resolution']['url'];
            }
            // if type is carousel then media is automatically transported
        } else {
            $media['scanned'] = 0;
            $media['user_id'] = $media['owner']['id'];
            unset($media['owner']);
            switch ($media['__typename']) {
                case 'GraphVideo':
                    $media['type'] = video;
                    break;
                case 'GraphImage':
                    $media['type'] = image;
                    break;
                case 'GraphSidecar':
                    $media['type'] = carousel;
                    break;
            }
            //
            if(isset($media['display_resources'])){
                $media['scanned'] = 1;
                $media['created_time'] = $media['taken_at_timestamp'];
                //
                $media['likes'] = $media['edge_media_preview_like']['count'];
                $media['comments'] = $media['edge_media_to_comment']['count'];
                $media['code'] = $media['shortcode'];
                $media['video_views'] = $media['video_view_count'];
                $media['link'] = 'https://instagram.com/p/' .$media['shortcode'];
                unset($media['edge_media_preview_like']);
                unset($media['edge_media_to_comment']);
                //related
                unset($media['edge_web_media_to_related_media']);//related
                //
                $media['caption'] = $media['edge_media_to_caption']['edges'][0]['node']['text'];
                //
                $media['thumbnail_src'] = $media['display_resources'][0]['src'];
                $media['img_thumbnail'] = $media['display_resources'][0]['src'];
                $media['img_standard_resolution'] = $media['display_resources'][2]['src'];
                $media['img_low_resolution'] = $media['display_resources'][1]['src'];
                //
                if ($media['is_video']){
                    $media['vid_low_bandwidth'] = $media['videos']['low_bandwidth']['url'];
                    $media['vid_low_resolution'] = $media['videos']['low_resolution']['url'];
                    $media['vid_standard_resolution'] = $media['video_url'];
                }
            } else {
                $media['created_time'] = $media['date'];
                //
                unset($media['dimensions']);
                //
                $media['likes'] = $media['likes']['count'];
                $media['comments'] = $media['comments']['count'];
                //
            }
        }
        //
        $this->assignMemberVariables($media);
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
