<html>
  <head>
   <style>
     @page { margin: 170px 30px; }
     .header { position: fixed; left: 0px; top: -150px; right: 0px; height: 150px;  text-align: center; }
     .footer { position: fixed; left: 0px; bottom: -180px; right: 0px; height: 50px;  }
     .footer .page:after { content: counter(page, upper-roman); }
     .content { margin-top: 0px;  }
      
   </style>
  <body style="font-size:13px">
<?php


function asMoney($value) {
  return number_format($value, 2);
}

?>

    
   <div class="header">
     <table >

      <tr>


       
        <td style="width:150px">

            <img src="{{asset('public/uploads/logo/'.$organization->logo)}}" alt="logo" width="150">
    
        </td>

        <td>
        <strong>
          {{ strtoupper($organization->name)}}<br>
          </strong>
          {{ $organization->phone}} |
          {{ $organization->email}} |
          {{ $organization->website}}<br>
          {{ $organization->address}}
       

        </td>

        <td>
          <strong><h3>{{$loans->name}} REPORT</h3></strong>

        </td>
        

      </tr>


      <tr>

        <hr>
      </tr>



    </table>
   </div>
   <div class="footer">
     <p class="page">Page <?php $PAGE_NUM ?></p>
   </div>



   <div class="content">
     

      <table class="table table-bordered" style="width:100%">


        <tr>

          <td style="border-bottom:1px solid black;"><strong>Member #</strong></td>
          <td style="border-bottom:1px solid black;"><strong>Member Name</strong></td>
          <td style="border-bottom:1px solid black;"><strong>Loan Product</strong></td>
          <td style="border-bottom:1px solid black;"><strong>Loan Number</strong></td>
          <td style="border-bottom:1px solid black;"><strong>Loan Amount</strong></td>
          <td style="border-bottom:1px solid black;"><strong>Loan Balance</strong></td>

        </tr>
        <?php $total = 0;?>
        @foreach($loans->loanaccounts as $loan)
        @if(Loantransaction::getLoanBalance($loan) > 5)
        <tr>
          <td style="border-bottom:0.1px solid black; border-right:0.1px solid black;">{{$loan->member->membership_no}}</td>
           <td style="border-bottom:0.1px solid black; border-right:0.1px solid black;">{{$loan->member->name}}</td>
           <td style="border-bottom:0.1px solid black; border-right:0.1px solid black;">{{$loan->loanproduct->name}}</td>
           <td style="border-bottom:0.1px solid black; border-right:0.1px solid black;">{{$loan->account_number}}</td>
            <td style="border-bottom:0.1px solid black; border-right:0.1px solid black;">{{asMoney(Loanaccount::getLoanAmount($loan))}}</td>
           <td style="border-bottom:0.1px solid black; border-right:0.1px solid black;">{{asMoney(Loantransaction::getLoanBalance($loan))}}</td>
        </tr>

        <?php $total = $total + Loantransaction::getLoanBalance($loan);?>
        @endif
        @endforeach
        
        <tr>
        <td align="right" colspan="5" style="border-bottom:0.1px solid black; border-right:0.1px solid black;"><strong>Total</strong></td>
        <td style="border-bottom:0.1px solid black; border-right:0.1px solid black;">{{asMoney($total,2)}}</td>
        </tr>

      </table>















    
   </div>








 </body>
 </html>