<?php

namespace Drupal\Tests\form_api_example\Functional;

use Drupal\Core\Url;
use Drupal\Tests\examples\Functional\ExamplesBrowserTestBase;

/**
 * Ensure that the form_api_example forms work properly.
 *
 * @group form_api_example
 * @group examples
 *
 * @ingroup form_api_example
 */
class FapiExampleTest extends ExamplesBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Our module dependencies.
   *
   * @var string[]
   */
  public static $modules = ['form_api_example'];

  /**
   * The installation profile to use with this test.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * Aggregate all the tests.
   *
   * Since this is a functional test, and we don't expect to need isolation
   * between these form tests, we'll aggregate them here for speed's sake. That
   * way the testing system doesn't have to rebuild a new Drupal for us for each
   * test.
   */
  public function testFunctional() {
    // Please fail this one first.
    $this->doTestRoutes();

    $this->doTestAjaxAddMore();
    $this->doTestAjaxColorForm();
    $this->doTestBuildDemo();
    $this->doTestContainerDemoForm();
    $this->doTestInputDemoForm();
    $this->doTestModalForm();
    $this->doTestSimpleFormExample();
    $this->doTestStateDemoForm();
    $this->doTestVerticalTabsDemoForm();
  }

  /**
   * Tests links.
   */
  public function doTestRoutes() {
    $assertion = $this->assertSession();

    // Routes with menu links, and their form buttons.
    $routes = [
      'form_api_example.description' => [],
      'form_api_example.simple_form' => ['Submit'],
      'form_api_example.input_demo' => ['Submit'],
      'form_api_example.state_demo' => ['Submit'],
      'form_api_example.container_demo' => ['Submit'],
      'form_api_example.vertical_tabs_demo' => ['Submit'],
      // Modal form has a submit button, but requires input.
      'form_api_example.modal_form' => [],
      'form_api_example.ajax_color_demo' => ['Submit'],
      'form_api_example.build_demo' => ['Submit'],
      'form_api_example.ajax_addmore' => ['Submit'],
      // Multistep form has submit buttons, but requires input.
      'form_api_example.multistep_form' => [],
    ];

    // Ensure the links appear in the tools menu sidebar.
    $this->drupalGet('');
    foreach (array_keys($routes) as $route) {
      $assertion->linkByHrefExists(Url::fromRoute($route)->getInternalPath());
    }

    // Go to all the routes and click all the buttons.
    foreach ($routes as $route => $buttons) {
      $path = Url::fromRoute($route);
      error_log($route);
      $this->drupalGet($path);
      $assertion->statusCodeEquals(200);
      foreach ($buttons as $button) {
        $this->drupalPostForm($path, [], $button);
        $assertion->statusCodeEquals(200);
      }
    }
  }

  /**
   * Test the ajax demo form.
   */
  public function doTestAjaxColorForm() {
    $assert = $this->assertSession();

    // Post the form.
    $edit = [
      'temperature' => 'warm',
    ];
    $this->drupalPostForm(Url::fromRoute('form_api_example.ajax_color_demo'), $edit, 'Submit');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Value for Temperature: warm');
  }

  /**
   * Test the build demo form.
   */
  public function doTestBuildDemo() {
    $assert = $this->assertSession();
    $build_demo_url = Url::fromRoute('form_api_example.build_demo');

    $edit = [
      'change' => '1',
    ];
    $this->drupalPostForm($build_demo_url, $edit, 'Submit');

    $assert->pageTextContains('1. __construct');
    $assert->pageTextContains('2. getFormId');
    $assert->pageTextContains('3. validateForm');
    $assert->pageTextContains('4. submitForm');

    // Ensure the 'submit rebuild' action performs the rebuild.
    $this->drupalPostForm($build_demo_url, $edit, 'Submit Rebuild');
    $assert->pageTextContains('4. rebuildFormSubmit');
  }

  /**
   * Test the container demo form.
   */
  public function doTestContainerDemoForm() {
    $assert = $this->assertSession();

    // Post the form.
    $edit = [
      'name' => 'Dave',
      'pen_name' => 'DMan',
      'title' => 'My Book',
      'publisher' => 'me',
      'diet' => 'vegan',
    ];
    $this->drupalPostForm(Url::fromRoute('form_api_example.container_demo'), $edit, 'Submit');
    $assert->pageTextContains('Value for name: Dave');
    $assert->pageTextContains('Value for pen_name: DMan');
    $assert->pageTextContains('Value for title: My Book');
    $assert->pageTextContains('Value for publisher: me');
    $assert->pageTextContains('Value for diet: vegan');
  }

  /**
   * Test the input demo form.
   */
  public function doTestInputDemoForm() {
    $assert = $this->assertSession();

    // Post the form.
    $edit = [
      'tests_taken[SAT]' => TRUE,
      'color' => '#2b49ff',
      'expiration' => '2015-10-21',
      'datetime[date]' => '2017-12-07 15:32:10',
      'url' => 'https://www.drupal.org',
      'email' => 'somebody@example.org',
      'quantity' => '4',
      'password' => 'letmein',
      'password_confirm[pass1]' => 'letmein',
      'password_confirm[pass2]' => 'letmein',
      'size' => '76',
      'active' => '1',
      'search' => 'my search string',
      'favorite' => 'blue',
      'select_multiple[]' => ['act'],
      'phone' => '555-555-5555',
      'table[1]' => TRUE,
      'table[3]' => TRUE,
      'text' => 'This is a test of my form.',
      'text_format[value]' => 'Examples for Developers',
      'subject' => 'Form test',
      'weight' => '3',
    ];
    $this->drupalPostForm(Url::fromRoute('form_api_example.input_demo'), $edit, 'Submit');
    $assert->statusCodeEquals(200);

    $assert->pageTextContains('Value for What standardized tests did you take?');
    $assert->pageTextContains('Value for Color: #2b49ff');
    $assert->pageTextContains('Value for Content expiration: 2015-10-21');
    $assert->pageTextContains('Value for Date Time: 2017-12-07 15:32:10');
    $assert->pageTextContains('Value for URL: https://www.drupal.org');
    $assert->pageTextContains('Value for Email: somebody@example.org');
    $assert->pageTextContains('Value for Quantity: 4');
    $assert->pageTextContains('Value for Password: letmein');
    $assert->pageTextContains('Value for New Password: letmein');
    $assert->pageTextContains('Value for Size: 76');
    $assert->pageTextContains('Value for active: 1');
    $assert->pageTextContains('Value for Search: my search string');
    $assert->pageTextContains('Value for Favorite color: blue');
    $assert->pageTextContains('Value for Select (multiple): Array ( [act] => act )');
    $assert->pageTextContains('Value for Phone: 555-555-5555');
    $assert->pageTextContains('Value for Users: Array ( [1] => 1 [3] => 3 )');
    $assert->pageTextContains('Value for Text: This is a test of my form.');
    $assert->pageTextContains('Value for Text format: Array ( [value] => Examples for Developers [format] => plain_text )');
    $assert->pageTextContains('Value for Subject: Form test');
    $assert->pageTextContains('Value for Weight: 3');
  }

  /**
   * Test the modal form.
   */
  public function doTestModalForm() {
    $assert = $this->assertSession();

    // Post the form.
    $edit = [
      'title' => 'My Book',
    ];
    $this->drupalPostForm(Url::fromRoute('form_api_example.modal_form'), $edit, 'Submit');
    $assert->pageTextContains('Submit handler: You specified a title of My Book.');
  }

  /**
   * Check routes defined by form_api_example.
   */
  public function doTestSimpleFormExample() {
    $assert = $this->assertSession();

    // Post a title.
    $edit = ['title' => 'My Custom Title'];
    $this->drupalPostForm(Url::fromRoute('form_api_example.simple_form'), $edit, 'Submit');
    $assert->pageTextContains('You specified a title of My Custom Title.');
  }

  /**
   * Test the state demo form.
   */
  public function doTestStateDemoForm() {
    $assert = $this->assertSession();

    // Post the form.
    $edit = [
      'needs_accommodation' => TRUE,
      'diet' => 'vegan',
    ];
    $this->drupalPostForm(Url::fromRoute('form_api_example.state_demo'), $edit, 'Submit');
    $assert->pageTextContains('Dietary Restriction Requested: vegan');
  }

  /**
   * Test the vertical tabs demo form.
   */
  public function doTestVerticalTabsDemoForm() {
    $assert = $this->assertSession();

    // Post the form.
    $edit = [
      'name' => 'Dave',
      'publisher' => 'me',
    ];
    $this->drupalPostForm(Url::fromRoute('form_api_example.container_demo'), $edit, 'Submit');
    $assert->pageTextContains('Value for name: Dave');
    $assert->pageTextContains('Value for publisher: me');
  }

  /**
   * Test the Ajax Add More demo form.
   */
  public function doTestAjaxAddMore() {
    // XPath for the remove button. We have to use contains() here because the
    // ID will have a hash value at the end.
    $button_xpath = '//input[contains(@id,"edit-names-fieldset-actions-remove-name")]';

    $ajax_addmore_url = Url::fromRoute('form_api_example.ajax_addmore');

    // Verify that anonymous can access the ajax_add_more page.
    $this->drupalGet($ajax_addmore_url);
    $this->assertResponse(200);
    // Verify that there is no remove button.
    $this->assertEmpty($this->xpath($button_xpath));

    $name_one = 'John';
    $name_two = 'Smith';

    // Enter the value in field-1.
    // and click on 'Add one more' button.
    $edit = [];
    $edit['names_fieldset[name][0]'] = $name_one;
    $this->drupalPostForm($ajax_addmore_url, $edit, 'Add one more');

    // Verify field-2 gets added.
    // and value of field-1 should retained.
    $this->assertFieldsByValue($this->xpath('//input[@id = "edit-names-fieldset-name-0"]'), $name_one);
    $this->assertNotEmpty($this->xpath('//input[@id = "edit-names-fieldset-name-1"]'));
    // Verify that the remove button was added.
    $this->assertNotEmpty($this->xpath($button_xpath));

    // Enter the value in field-2
    // and click on 'Add one more' button.
    $edit['names_fieldset[name][1]'] = $name_two;
    $this->drupalPostForm(NULL, $edit, 'Add one more');

    // Verify field-3 gets added.
    // and value of field-1 and field-2 are retained.
    $this->assertFieldsByValue($this->xpath('//input[@id = "edit-names-fieldset-name-0"]'), $name_one);
    $this->assertFieldsByValue($this->xpath('//input[@id = "edit-names-fieldset-name-1"]'), $name_two);
    $this->assertNotEmpty($this->xpath('//input[@id = "edit-names-fieldset-name-2"]'));

    // Click on "Remove one" button to test remove button works.
    // and value of field-1 and field-2 are retained.
    $this->drupalPostForm(NULL, NULL, 'Remove one');
    $this->assertFieldsByValue($this->xpath('//input[@id = "edit-names-fieldset-name-0"]'), $name_one);
    $this->assertFieldsByValue($this->xpath('//input[@id = "edit-names-fieldset-name-1"]'), $name_two);
    $this->assertEmpty($this->xpath('//input[@id = "edit-names-fieldset-name-2"]'));

    // Submit the form and verify the results.
    $this->drupalPostForm(NULL, NULL, 'Submit');
    $this->assertText('These people are coming to the picnic: ' . $name_one . ', ' . $name_two);

  }

}
