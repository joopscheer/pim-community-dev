<?php
namespace Pim\Bundle\ConnectorIcecatBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
/**
 * Configs entities use for ConfigsType
 *
 * @author    Romain Monceau <romain@akeneo.com>
 * @copyright 2012 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
class Configs
{
    /**
     * @var ArrayCollection
     */
    protected $configs;

    /**
     * Constructor for Config collection
     *
     * @param array $configs
     */
    public function __construct(array $configs = array())
    {
        $this->configs = new ArrayCollection($configs);
    }

    /**
     * Get configs
     *
     * @return ArrayCollection
     */
    public function getConfigs()
    {
        return $this->configs;
    }

    /**
     * Set configs
     * @param array $configs
     */
    public function setConfigs($configs)
    {
        $this->configs = $configs;
    }
}
