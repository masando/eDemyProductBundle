<?php

namespace eDemy\ProductBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProductControllerTest extends WebTestCase
{
    public function testCompleteScenario()
    {
        // Create a new client to browse the application
        $client = static::createClient();

        // Create a new entry in the database
        $crawler = $client->request('GET', '/es/product/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode(), "Unexpected HTTP status code for GET /product/");
        $crawler = $client->click($crawler->selectLink('Create a new entry')->link());

		$value = $crawler->filter('#edemy_mainbundle_product_category option:contains("cat1")')->attr('value');
		
        // Fill in the form and submit it
        $form = $crawler->selectButton('Create')->form(array(
            'edemy_mainbundle_product[name]'  => 'Test',
			'edemy_mainbundle_product[description]'  => 'Test description',
			'edemy_mainbundle_product[price]'  => 10.02,
        ));
		$path = __DIR__."/../../Resources/public/images/";
		$filename = "test_image1.jpg";
		$tmpname = "tmp.jpg";
		copy($path . $filename, $path . $tmpname);
		
		$form['edemy_mainbundle_product[file]']->upload($path.$tmpname);
		$form['edemy_mainbundle_product[category]']->select($value);
		
        $client->submit($form);
        $crawler = $client->followRedirect();

        // Check data in the show view
        $this->assertGreaterThan(0, $crawler->filter('td:contains("Test")')->count(), 'Missing element td:contains("Test")');

        // Edit the entity
        $crawler = $client->click($crawler->selectLink('Edit')->link());
		//$value = $crawler->filter('#edemy_mainbundle_product_category option:contains("cat2")')->attr('value');
        $form = $crawler->selectButton('Update')->form(array(
            'edemy_mainbundle_product[name]'  => 'Foo',
			'edemy_mainbundle_product[description]'  => 'Foo',
			'edemy_mainbundle_product[price]'  => 10.02,
        ));
		//$form['edemy_mainbundle_product[category]']->select($value);

        $client->submit($form);
        $crawler = $client->followRedirect();

        // Check the element contains an attribute with value equals "Foo"
        $this->assertGreaterThan(0, $crawler->filter('[value="Foo"]')->count(), 'Missing element [value="Foo"]');

        // Delete the entity
        $client->submit($crawler->selectButton('Delete')->form());
        $crawler = $client->followRedirect();

        // Check the entity has been delete on the list
        $this->assertNotRegExp('/Foo/', $client->getResponse()->getContent());
    }
}
