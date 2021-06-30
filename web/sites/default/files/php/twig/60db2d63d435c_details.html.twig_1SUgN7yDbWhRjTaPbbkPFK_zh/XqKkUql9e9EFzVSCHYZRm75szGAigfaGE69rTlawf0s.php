<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* core/themes/seven/templates/details.html.twig */
class __TwigTemplate_ad2ff97ca4a96e6d04ba3c39d34a7d27b21811ec4e5605002c0359afb4dd8e25 extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->env->getExtension('\Twig\Extension\SandboxExtension');
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 23
        echo "<details";
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", [0 => "seven-details"], "method", false, false, true, 23), 23, $this->source), "html", null, true);
        echo ">";
        // line 24
        if (($context["title"] ?? null)) {
            // line 26
            $context["summary_classes"] = [0 => "seven-details__summary", 1 => ((            // line 28
($context["required"] ?? null)) ? ("js-form-required") : ("")), 2 => ((            // line 29
($context["required"] ?? null)) ? ("form-required") : (""))];
            // line 32
            echo "    <summary";
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, ($context["summary_attributes"] ?? null), "addClass", [0 => ($context["summary_classes"] ?? null)], "method", false, false, true, 32), 32, $this->source), "html", null, true);
            echo ">
      <span>";
            // line 33
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["title"] ?? null), 33, $this->source), "html", null, true);
            echo "</span>
    </summary>";
        }
        // line 36
        echo "<div class=\"seven-details__wrapper details-wrapper\">
    ";
        // line 37
        if (($context["errors"] ?? null)) {
            // line 38
            echo "      <div class=\"form-item form-item--error-message\">
        <strong>";
            // line 39
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["errors"] ?? null), 39, $this->source), "html", null, true);
            echo "</strong>
      </div>
    ";
        }
        // line 42
        if (($context["description"] ?? null)) {
            // line 43
            echo "<div class=\"seven-details__description\">";
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["description"] ?? null), 43, $this->source), "html", null, true);
            echo "</div>";
        }
        // line 45
        if (($context["children"] ?? null)) {
            // line 46
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["children"] ?? null), 46, $this->source), "html", null, true);
        }
        // line 48
        if (($context["value"] ?? null)) {
            // line 49
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["value"] ?? null), 49, $this->source), "html", null, true);
        }
        // line 51
        echo "</div>
</details>
";
    }

    public function getTemplateName()
    {
        return "core/themes/seven/templates/details.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  90 => 51,  87 => 49,  85 => 48,  82 => 46,  80 => 45,  75 => 43,  73 => 42,  67 => 39,  64 => 38,  62 => 37,  59 => 36,  54 => 33,  49 => 32,  47 => 29,  46 => 28,  45 => 26,  43 => 24,  39 => 23,);
    }

    public function getSourceContext()
    {
        return new Source("", "core/themes/seven/templates/details.html.twig", "/var/www/html/web/core/themes/seven/templates/details.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = array("if" => 24, "set" => 26);
        static $filters = array("escape" => 23);
        static $functions = array();

        try {
            $this->sandbox->checkSecurity(
                ['if', 'set'],
                ['escape'],
                []
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }
}
