<?php
namespace Oro\Bundle\CurrencyBundle\Migrations\Data\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfig;

class SetDefaultCurrencyFromLocale extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        /**@var ConfigManager $configManager **/
        $configManager = $this->container->get('oro_config.global');

        $connection = $this->container->get('doctrine')->getConnection();

        $currencies = $connection->fetchAll('
                                              SELECT 
                                                oro_config_value.text_value
                                              FROM
                                                oro_config_value
                                              WHERE
                                                oro_config_value.name = \'default_currency\'
         ');

        /**
         * If currency already set
         * do nothing
         */
        if (count($currencies)) {
            return;
        }

        $configManager->set(
            CurrencyConfig::getConfigKeyByName(CurrencyConfig::KEY_DEFAULT_CURRENCY),
            CurrencyConfig::DEFAULT_CURRENCY
        );

        $configManager->flush();
    }
}
