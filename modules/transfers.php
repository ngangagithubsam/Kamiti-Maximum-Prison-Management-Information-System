<!-- TRANSFERS -->
      <div class="page" id="page-transfers">
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-label">Pending transfer requests</div>
            <div class="stat-value" id="stat-pending-transfers">0</div>
            <div class="stat-sub">Requests awaiting approval</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Approved transfers</div>
            <div class="stat-value" id="stat-approved-transfers">0</div>
            <div class="stat-sub">Successful transfer actions</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Declined transfers</div>
            <div class="stat-value" id="stat-declined-transfers">0</div>
            <div class="stat-sub">Requests that were declined</div>
          </div>
        </div>

        <div class="table-card">
          <div class="table-header">
            <div class="table-title">Submit Transfer Request</div>
            <div class="search-box"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg><input type="text" placeholder="Search requests..." id="transfer-search" oninput="renderTransfers()"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Prisoner</label><select class="form-select" id="transfer-prisoner" onchange="updateTransferFromFacility()"></select></div>
            <div class="form-group"><label class="form-label">From facility</label><input class="form-input" id="transfer-from" placeholder="Current facility"></div>
            <div class="form-group"><label class="form-label">Destination facility</label><input class="form-input" id="transfer-to" placeholder="Enter destination facility"></div>
          </div>
          <div class="form-group"><label class="form-label">Reason for transfer</label><textarea class="form-textarea" id="transfer-reason" placeholder="Explain why the transfer is needed"></textarea></div>
          <div class="flex-between" style="gap:12px;">
            <button class="btn btn-secondary btn-sm" onclick="resetTransferForm()">Reset</button>
            <button class="btn btn-primary btn-sm" onclick="submitTransferRequest()">Submit Transfer</button>
          </div>
        </div>

        <div class="table-card">
          <div class="table-header">
            <div class="table-title">Transfer Requests</div>
            <div class="filter-group"><label>Status:</label><select class="form-select" id="transfer-status-filter" onchange="renderTransfers()"><option value="">All</option><option>Pending</option><option>Approved</option><option>Declined</option></select></div>
          </div>
          <table><thead><tr><th>Request</th><th>Prisoner</th><th>From</th><th>To</th><th>Status</th><th>Requested</th><th>Review</th><th>Action</th></tr></thead><tbody id="transfer-requests-tbody"></tbody></table>
        </div>
      </div>
