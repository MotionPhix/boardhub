<div class="section">
  <h2 class="section-title">1. DEFINITIONS AND INTERPRETATION</h2>

  <div class="subsection">
    <p>In this Agreement, unless the context otherwise requires, the following terms shall have the meanings set forth below:</p>

    <table class="definitions-table">
      <tbody>
      <tr>
        <td style="width: 30%;"><strong>"Agreement"</strong></td>
        <td>means this Premium Advertising Agreement including all schedules and annexures hereto;</td>
      </tr>
      <tr>
        <td><strong>"Advertising Content"</strong></td>
        <td>means any and all materials, artwork, designs, text, graphics, or other content provided by the Client for display on the Advertising Space;</td>
      </tr>
      <tr>
        <td><strong>"Advertising Space"</strong></td>
        <td>means the billboard(s) and/or advertising locations specified in Section 3.1 of this Agreement;</td>
      </tr>
      <tr>
        <td><strong>"Business Day"</strong></td>
        <td>means any day other than a Saturday, Sunday, or public holiday in {{ $settings->getCompanyProfile()['address']['country'] }};</td>
      </tr>
      <tr>
        <td><strong>"Commencement Date"</strong></td>
        <td>means {{ $contract->start_date->format('jS F Y') }};</td>
      </tr>
      <tr>
        <td><strong>"Confidential Information"</strong></td>
        <td>includes all information exchanged between the parties relating to this Agreement, the Advertising Space, pricing, business processes, and technical details;</td>
      </tr>
      <tr>
        <td><strong>"Contract Period"</strong></td>
        <td>means the period from the Commencement Date to {{ $contract->end_date->format('jS F Y') }}, inclusive;</td>
      </tr>
      <tr>
        <td><strong>"Force Majeure Event"</strong></td>
        <td>means any event beyond the reasonable control of either party, including but not limited to natural disasters, war, civil unrest, or governmental actions;</td>
      </tr>
      <tr>
        <td><strong>"Intellectual Property Rights"</strong></td>
        <td>means all patents, trademarks, service marks, designs, copyright, trade secrets, know-how, and other intellectual property rights;</td>
      </tr>
      <tr>
        <td><strong>"Monthly Fee"</strong></td>
        <td>means the sum of {{ $contract->currency->symbol }} {{ number_format($contract->contract_final_amount, 2) }} payable monthly in advance;</td>
      </tr>
      <tr>
        <td><strong>"Service Level Agreement"</strong></td>
        <td>means the maintenance and service standards set forth in Section 4 of this Agreement;</td>
      </tr>
      </tbody>
    </table>
  </div>

  <div class="subsection mt-4">
    <h3>1.2 Interpretation</h3>
    <p>In this Agreement, unless the context otherwise requires:</p>
    <ol type="a" class="ml-4">
      <li>words importing the singular include the plural and vice versa;</li>
      <li>words importing any gender include all genders;</li>
      <li>references to persons include corporations and other entities;</li>
      <li>references to clauses and schedules are to clauses of and schedules to this Agreement;</li>
      <li>headings are for convenience only and do not affect interpretation;</li>
      <li>references to currency are to {{ $contract->currency->code }} unless otherwise specified;</li>
      <li>if any payment under this Agreement is due on a day that is not a Business Day, the payment shall be made on the next Business Day.</li>
    </ol>
  </div>
</div>
