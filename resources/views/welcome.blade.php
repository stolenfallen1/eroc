<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Appointment</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Appointment Management</h2>

        <!-- Add Appointment Button -->
        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addAppointmentModal">Add Appointment</button>

        <!-- Add Appointment Modal -->
        <div class="modal fade" id="addAppointmentModal" tabindex="-1" aria-labelledby="addAppointmentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <form id="appointmentForm"  method="POST" enctype="multipart/form-data">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addAppointmentModalLabel">Add Appointment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          
                            <!-- Image Upload -->
                            <div class="mb-3">
                                <label for="doctor_request_image" class="form-label">Doctor Request Image</label>
                                <input type="file" name="payment_UploadPath" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Save Appointment</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add or Remove Items Dynamically
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('items-container');
            container.addEventListener('click', function(e) {
                if (e.target.classList.contains('add-item')) {
                    e.preventDefault();
                    const row = `
                        <div class="row align-items-end mb-2">
                            <div class="col-md-3">
                                <input type="text" class="form-control" name="item_id[1297,12296]" placeholder="Enter Item ID" required>
                            </div>
                            <div class="col-md-3">
                                <input type="number" class="form-control" name="quantity[]" placeholder="Enter Quantity" required>
                            </div>
                            <div class="col-md-3">
                                <input type="number" class="form-control" name="amount[5000,3100]" placeholder="Enter Amount" required>
                            </div>
                            <div class="col-md-3">
                                <button type="button" class="btn btn-danger remove-item">-</button>
                            </div>
                        </div>`;
                    container.insertAdjacentHTML('beforeend', row);
                } else if (e.target.classList.contains('remove-item')) {
                    e.target.closest('.row').remove();
                }
            });
        });
    </script>
</body>

</html>
