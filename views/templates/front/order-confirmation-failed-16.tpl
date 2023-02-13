<p>
    {l s='An error occurred during your payment.' mod='ps_mesomb'}<br /><br />
  <span style="font-style: italic; color: red;">{$message}</span><br /><br />
    {{l s='Please [a @href1@]try again[/a] or contact the website owner.' mod='ps_mesomb'}|mesomblreplace:['@href1@' => {{$mesomb_order_url|escape:'htmlall'}}]}
</p>
