<div class="row" data-payroll-id="{{ $payroll->id }}">

<div class="card p-3 mb-3">

    <div class="row g-2">

        <div class="col-md-4">
            <input type="text" id="newDesc" class="form-control" placeholder="Description">
        </div>

        <div class="col-md-3">
            <select id="newType" class="form-select">
                <option value="earning">Earning</option>
                <option value="deduction">Deduction</option>
            </select>
        </div>

        <div class="col-md-3">
            <input type="number" id="newAmount" class="form-control" placeholder="Amount">
        </div>

        <div class="col-md-2">
            <button class="btn btn-success w-100" onclick="addItem()">
                Add
            </button>
        </div>

    </div>

</div>

    <div class="col-md-6">

        <h6>Earnings</h6>

        <table class="table table-sm" id="earningsTable">

            @foreach($payroll->items->where('type','earning') as $item)

            <tr data-id="{{ $item->id }}">

                <td>
                    <input type="text" value="{{ $item->description }}" class="form-control form-control-sm"
                        onblur="updateItem(this, {{ $item->id }}, 'description')">
                </td>

                <td>
                    <input type="number" value="{{ $item->amount }}" class="form-control form-control-sm text-end"
                        onblur="updateItem(this, {{ $item->id }}, 'amount')">
                </td>

                <td class="text-end">
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteItem({{ $item->id }})">
                        Delete
                    </button>
                </td>

            </tr>

            @endforeach

        </table>

    </div>

    <div class="col-md-6">

        <h6>Deductions</h6>

            <table class="table table-sm" id="deductionsTable">

            @foreach($payroll->items->where('type','deduction') as $item)

            <tr data-id="{{ $item->id }}">

                <td>
                    <input type="text" value="{{ $item->description }}" class="form-control form-control-sm"
                        onblur="updateItem(this, {{ $item->id }}, 'description')">
                </td>

                <td>
                    <input type="number" value="{{ $item->amount }}" class="form-control form-control-sm text-end"
                        onblur="updateItem(this, {{ $item->id }}, 'amount')">
                </td>

                <td class="text-end">
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteItem({{ $item->id }})">
                        Delete
                    </button>
                </td>

            </tr>

            @endforeach

        </table>

    </div>

</div>