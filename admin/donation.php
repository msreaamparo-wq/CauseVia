<?php include 'header.php'; ?>

<?php
// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['donation_id']) && isset($_POST['status']) && isset($_POST['type'])) {
        $donation_id = $_POST['donation_id'];
        $status = $_POST['status'];
        $type = $_POST['type'];
        
        if ($type === 'project') {
            // Update project donation
            $query = "UPDATE donations SET status = $1 WHERE id = $2";
            executeQuery($query, [$status, $donation_id]);
            
            // If approved, update project current amount
            if ($status === 'approved') {
                $query = "SELECT amount, project_id FROM donations WHERE id = $1";
                $result = fetchSingle($query, [$donation_id]);
                if ($result && $result['project_id']) {
                    $update_query = "UPDATE projects SET current_amount = current_amount + $1 WHERE id = $2";
                    executeQuery($update_query, [$result['amount'], $result['project_id']]);
                }
            }
        } else {
            // Update general donation
            $query = "UPDATE general_donation SET status = $1 WHERE general_donation_id = $2";
            executeQuery($query, [$status, $donation_id]);
        }
        
        // Refresh page
        header("Location: donation.php");
        exit();
    }
}

// Handle project status updates
if (isset($_POST['project_id']) && isset($_POST['project_status'])) {
    $query = "UPDATE projects SET status = $1 WHERE id = $2";
    executeQuery($query, [$_POST['project_status'], $_POST['project_id']]);
    header("Location: donation.php");
    exit();
}

// Get project donations
$project_donations = fetchAll("
    SELECT d.*, u.full_name, u.email, p.project_title 
    FROM donations d 
    JOIN users u ON d.user_id = u.id 
    LEFT JOIN projects p ON d.project_id = p.id 
    ORDER BY d.created_at DESC
");

// Get general donations
$general_donations = fetchAll("
    SELECT gd.*, u.full_name, u.email 
    FROM general_donation gd 
    JOIN users u ON gd.user_id = u.id 
    ORDER BY gd.donation_date DESC
");
?>

<div class="container-fluid">
    <h1 class="page-title">Donation Management</h1>
    
    <ul class="nav nav-tabs mb-4" id="donationTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="project-tab" data-bs-toggle="tab" data-bs-target="#project-donations" type="button">
                <i class="fas fa-project-diagram"></i> Project Donations
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="general-tab" data-bs-toggle="tab" data-bs-target="#general-donations" type="button">
                <i class="fas fa-hand-holding-heart"></i> General Donations
            </button>
        </li>
    </ul>
    
    <div class="tab-content" id="donationTabsContent">
        <!-- Project Donations Tab -->
        <div class="tab-pane fade show active" id="project-donations" role="tabpanel">
            <div class="card card-custom">
                <div class="card-header card-header-custom">
                    <h5 class="mb-0">Project Donations</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-header-custom">
                                <tr>
                                    <th>Donor</th>
                                    <th>Project</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Reference</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Proof</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($project_donations as $donation): ?>
                                <tr class="<?php echo $donation['status'] == 'pending' ? 'table-warning' : ''; ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars($donation['full_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($donation['email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($donation['project_title'] ?? 'N/A'); ?></td>
                                    <td>₱<?php echo number_format($donation['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($donation['payment_method']); ?></td>
                                    <td><code><?php echo htmlspecialchars($donation['transaction_ref']); ?></code></td>
                                    <td><?php echo date('M d, Y', strtotime($donation['created_at'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($donation['status']); ?>">
                                            <?php echo ucfirst($donation['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($donation['payment_proof']): ?>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="viewProof('<?php echo htmlspecialchars($donation['payment_proof']); ?>')">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <?php else: ?>
                                        <span class="text-muted">No proof</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($donation['status'] == 'pending'): ?>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-success" 
                                                    onclick="updateDonationStatus(<?php echo $donation['id']; ?>, 'approved', 'project')">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button class="btn btn-danger" 
                                                    onclick="updateDonationStatus(<?php echo $donation['id']; ?>, 'rejected', 'project')">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-muted">Processed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (count($project_donations) == 0): ?>
                                <tr>
                                    <td colspan="9" class="text-center">No project donations found.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- General Donations Tab -->
        <div class="tab-pane fade" id="general-donations" role="tabpanel">
            <div class="card card-custom">
                <div class="card-header card-header-custom">
                    <h5 class="mb-0">General Donations</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-header-custom">
                                <tr>
                                    <th>Donor</th>
                                    <th>Type</th>
                                    <th>Amount/Items</th>
                                    <th>Payment Method</th>
                                    <th>Reference</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Proof</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($general_donations as $donation): 
                                    $isInKind = $donation['donation_type'] == 'inkind';
                                ?>
                                <tr class="<?php echo $donation['status'] == 'pending' ? 'table-warning' : ''; ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars($donation['full_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($donation['email']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($isInKind): ?>
                                        <span class="badge bg-info">In-Kind</span>
                                        <?php else: ?>
                                        <span class="badge bg-primary">Cash</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isInKind): ?>
                                        <?php echo $donation['quantity']; ?> <?php echo $donation['unit']; ?> of<br>
                                        <small><?php echo htmlspecialchars($donation['item_description']); ?></small>
                                        <?php else: ?>
                                        ₱<?php echo number_format($donation['amountm'], 2); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($donation['payment_method']); ?></td>
                                    <td><code><?php echo htmlspecialchars($donation['reference_number']); ?></code></td>
                                    <td><?php echo date('M d, Y', strtotime($donation['donation_date'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($donation['status']); ?>">
                                            <?php echo ucfirst($donation['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($donation['proof']): ?>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="viewProof('<?php echo htmlspecialchars($donation['proof']); ?>')">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <?php else: ?>
                                        <span class="text-muted">No proof</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($donation['status'] == 'pending'): ?>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-success" 
                                                    onclick="updateDonationStatus(<?php echo $donation['general_donation_id']; ?>, 'approved', 'general')">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button class="btn btn-danger" 
                                                    onclick="updateDonationStatus(<?php echo $donation['general_donation_id']; ?>, 'rejected', 'general')">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-muted">Processed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (count($general_donations) == 0): ?>
                                <tr>
                                    <td colspan="9" class="text-center">No general donations found.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Proof Modal -->
<div class="modal fade" id="proofModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title">Payment Proof</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="proofImage" src="" alt="Payment Proof" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>