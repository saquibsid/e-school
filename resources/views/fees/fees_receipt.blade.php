<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <style>
            * { font-family: DejaVu Sans, sans-serif; }
        </style>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Fees Receipt || {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/css/bootstrap.min.css" integrity="sha512-P5MgMn1jBN01asBgU0z60Qk4QxiXo86+wlFahKrsQf37c9cro517WzVSPPV1tDKzhku2iJ2FVgL67wG03SGnNA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div class="container ">
        <div class="row mt-4">
            <div class="col">
                <div class="row">
                    <div class="col">
                        <div class="text-center">
                            <i><img style="height: 5rem;width: 5rem;"  src="{{$logo}}" alt="logo"></i>
                            <br>
                            <span class="text-default-d3 ml-4" style="font-size:1.5rem"><strong>{{$school_name}}</strong></span>
                            <br>
                            <span class="text-default-d3 ml-4" style="font-size:1rem">{{$school_address}}</span>
                            <hr width="auto" style="border: 1px solid">
                            <h4>
                                Fee Receipt
                            </h4>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-6">
                    </div>

                    <div class="col-sm-6 align-self-start d-sm-flex justify-content-end">
                        <div class="text-grey-m2 mt-2">
                            <p><strong><u>Invoice</u></strong><br>
                                @if(isset($fees_paid) && $fees_paid->mode == 0)
                                <strong>Payment Mode</strong> :- Cash <br>
                                @elseif(isset($fees_paid) && $fees_paid->mode == 1)
                                <strong>Payment Mode</strong> :- Cheque <br>
                                @elseif(isset($fees_paid) && $fees_paid->mode == 2)
                                <strong>Payment Mode</strong> :- Online <br>
                                @endif
                                <strong>Fee Receipt</strong> :- {{isset($fees_paid) ? $fees_paid->id : '-'}}<br>
                                <strong>Payment Date :- </strong> {{isset($fees_paid) ? $fees_paid->date : '-'}}
                            </p>
                        </div>
                    </div>
                </div>
                <hr style="border: 1px solid">
                <div class="row ml-3">
                    <div class="col-sm-6 align-self-start">
                        <div class="row text-black">
                            <p><strong><u>Student Details :- </u></strong><br>
                            <strong>Name</strong> :- {{isset($fees_paid) ? $fees_paid->student->user->first_name.' '.$fees_paid->student->user->last_name : '-'}} <br>
                            <strong>Session</strong> :- {{isset($fees_paid) ? $fees_paid->session_year->name : '-'}} <br>
                            <strong>Class</strong> :- {{isset($fees_paid) ? $fees_paid->class->name.' - '.$fees_paid->class->medium->name : '-'}}<br>
                        </div>
                    </div>
                </div>
                <div class="mt-4 ml-4">
                    <table class="table" style="text-align: center">
                        <thead>
                          <tr>
                            <th scope="col">Sr no.</th>
                            <th scope="col" colspan="2">Fee Type</th>
                            <th scope="col">Amount</th>
                          </tr>
                        </thead>
                        @php
                            $no = 1;
                            $amount = 0;
                        @endphp
                        <tbody>
                        @foreach ($fees_choiceable_db as $fees_choiceable)
                        @php
                            $amount += $fees_choiceable->total_amount;
                        @endphp
                        @if($fees_choiceable->is_due_charges == 1)
                            <tr>
                                <th scope="row">{{$no++}}</th>
                                <td colspan="2">Due Charges</td>
                                <td>{{$fees_choiceable->total_amount}} {{$currency_symbol}}</td>
                            </tr>
                        @else
                        <tr>
                            <th scope="row">{{$no++}}</th>
                            <td colspan="2">{{$fees_choiceable->fees_type->name}}</td>
                            <td>{{$fees_choiceable->total_amount}} {{$currency_symbol}}</td>
                        </tr>
                        @endif
                        @endforeach
                        <tr>
                            <th scope="row"></th>
                            <td colspan="2"><strong>Total Amount:-</strong></td>
                            <td>{{$amount}} {{$currency_symbol}}</td>
                          </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</body>

</html>
