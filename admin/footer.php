    </div> <!-- Close main-content -->

    <!-- Footer -->
    <footer class="footer mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; <?php echo date('Y'); ?> Alumni Crowdfunding System. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>Admin Panel v1.0 | Powered by PHP & PostgreSQL</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Confirm actions
        function confirmAction(message) {
            return confirm(message);
        }

        // View payment proof
        function viewProof(imageUrl) {
            const modal = new bootstrap.Modal(document.getElementById('proofModal'));
            document.getElementById('proofImage').src = imageUrl;
            modal.show();
        }

        // Update donation status
        function updateDonationStatus(donationId, status, type = 'project') {
            if (confirmAction(`Are you sure you want to ${status} this donation?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.href;
                
                const inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'donation_id';
                inputId.value = donationId;
                form.appendChild(inputId);
                
                const inputStatus = document.createElement('input');
                inputStatus.type = 'hidden';
                inputStatus.name = 'status';
                inputStatus.value = status;
                form.appendChild(inputStatus);
                
                const inputType = document.createElement('input');
                inputType.type = 'hidden';
                inputType.name = 'type';
                inputType.value = type;
                form.appendChild(inputType);
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Update project status
        function updateProjectStatus(projectId, status) {
            if (confirmAction(`Are you sure you want to ${status} this project?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.href;
                
                const inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'project_id';
                inputId.value = projectId;
                form.appendChild(inputId);
                
                const inputStatus = document.createElement('input');
                inputStatus.type = 'hidden';
                inputStatus.name = 'project_status';
                inputStatus.value = status;
                form.appendChild(inputStatus);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>

<?php
// Close database connection if exists
if (isset($dbconn)) {
    pg_close($dbconn);
}
?>