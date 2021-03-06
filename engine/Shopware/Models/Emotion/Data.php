<?php
/**
 * Shopware 4.0
 * Copyright © 2012 shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 *
 * @category   Shopware
 * @package    Shopware_Models
 * @subpackage Emotion
 * @copyright  Copyright (c) 2011-2012, shopware AG (http://www.shopware.de)
 * @license    http://shopware.de/license
 * @version    $Id$
 * @author     $Author$
 */
namespace   Shopware\Models\Emotion;
use         Shopware\Components\Model\ModelEntity,
            Doctrine\ORM\Mapping AS ORM;

/**
 *
 *
 * Associations:
 * <code>
 *
 * </code>
 *
 *
 * Indices:
 * <code>
 *
 * </code>
 *
 * @category   Shopware
 * @package    Models
 * @subpackage Emotion
 * @copyright  Copyright (c) 2011, shopware AG (http://www.shopware.de)
 * @license    http://enlight.de/license     New BSD License
 *
 * @ORM\Entity
 * @ORM\Table(name="s_emotion_element_value")
 */
class Data extends ModelEntity
{
    /**
     * Unique identifier field for the shopware emotion.
     *
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * Contains the id of the emotion
     *
     * @var string $emotionId
     *
     * @ORM\Column(name="emotionID", type="integer", nullable=false)
     */
    private $emotionId;

    /**
     * Contains the name of the emotion.
     *
     * @var string $elementId
     *
     * @ORM\Column(name="elementID", type="integer", nullable=false)
     */
    private $elementId;

    /**
     * Contains the id of the assigned element component
     * @var integer $componentId
     * @ORM\Column(name="componentID", type="integer", nullable=false)
     */
    private $componentId;

    /**
     * @var integer $fieldId
     * @ORM\Column(name="fieldID", type="integer", nullable=false)
     */
    private $fieldId;

    /**
     * @var string $value
     * @ORM\Column(name="value", type="text", nullable=true)
     */
    private $value = null;

    /**
     * @ORM\ManyToOne(targetEntity="\Shopware\Models\Emotion\Element", inversedBy="data")
     * @ORM\JoinColumn(name="elementID", referencedColumnName="id")
     */
    protected $element;

    /**
     * @ORM\OneToOne(targetEntity="\Shopware\Models\Emotion\Library\Component")
     * @ORM\JoinColumn(name="componentID", referencedColumnName="id")
     */
    protected $component;

    /**
     * @ORM\OneToOne(targetEntity="\Shopware\Models\Emotion\Library\Field")
     * @ORM\JoinColumn(name="fieldID", referencedColumnName="id")
     */
    protected $field;

    /**
     * @param int $componentId
     */
    public function setComponentId($componentId)
    {
        $this->componentId = $componentId;
    }

    /**
     * @return int
     */
    public function getComponentId()
    {
        return $this->componentId;
    }

    /**
     * @param string $elementId
     */
    public function setElementId($elementId)
    {
        $this->elementId = $elementId;
    }

    /**
     * @return string
     */
    public function getElementId()
    {
        return $this->elementId;
    }

    /**
     * @param int $fieldId
     */
    public function setFieldId($fieldId)
    {
        $this->fieldId = $fieldId;
    }

    /**
     * @return int
     */
    public function getFieldId()
    {
        return $this->fieldId;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    public function getField()
    {
        return $this->field;
    }

    public function setField($field)
    {
        $this->field = $field;
    }

    public function getComponent()
    {
        return $this->component;
    }

    public function setComponent($component)
    {
        $this->component = $component;
    }

    public function getElement()
    {
        return $this->element;
    }

    public function setElement($element)
    {
        $this->element = $element;
    }

    /**
     * @return string
     */
    public function getEmotionId()
    {
        return $this->emotionId;
    }

    /**
     * @param string $emotionId
     */
    public function setEmotionId($emotionId)
    {
        $this->emotionId = $emotionId;
    }
}
