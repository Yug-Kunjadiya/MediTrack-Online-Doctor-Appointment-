<?php
require_once 'partials/doctor_header.php'; // Includes db.php, session_start(), check_login('doctor')
$base_url = "/meditrack";
$doctor_id = $_SESSION['id'];

// Fetch current availability
$stmt_avail = mysqli_prepare($conn, "SELECT available_slots_json FROM doctors WHERE id = ?");
mysqli_stmt_bind_param($stmt_avail, "i", $doctor_id);
mysqli_stmt_execute($stmt_avail);
$result_avail = mysqli_stmt_get_result($stmt_avail);
$doctor_data = mysqli_fetch_assoc($result_avail);
mysqli_stmt_close($stmt_avail);

$current_availability_json_for_js = '[]'; // Default to an empty JSON array string for JS
if ($doctor_data && !empty($doctor_data['available_slots_json'])) {
    $decoded_db_json = json_decode($doctor_data['available_slots_json'], true);
    // Basic validation and normalization for JS
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_db_json)) {
        $normalized_data_for_js = [];
        foreach ($decoded_db_json as $date_key => $time_slots_array) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_key) && is_array($time_slots_array)) {
                $clean_slots = [];
                foreach ($time_slots_array as $time_val) {
                    if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time_val)) { // Ensure HH:MM
                        $clean_slots[] = $time_val;
                    }
                }
                if (!empty($clean_slots)) {
                    sort($clean_slots);
                    $normalized_data_for_js[$date_key] = array_unique($clean_slots);
                }
            }
        }
        if (!empty($normalized_data_for_js)) {
            ksort($normalized_data_for_js);
            $current_availability_json_for_js = json_encode($normalized_data_for_js);
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['full_availability_json'])) {
    $new_availability_json_from_form = $_POST['full_availability_json'];
    $decoded_new_availability = json_decode($new_availability_json_from_form, true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_new_availability)) {
        $final_json_to_store_array = [];
        // Strict validation of structure and format from JS
        foreach($decoded_new_availability as $date_key => $time_slots_array) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_key) && is_array($time_slots_array) && !empty($time_slots_array)) {
                $valid_slots_for_date = [];
                foreach ($time_slots_array as $time_val) {
                    if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time_val)) { // Expect HH:MM
                        $valid_slots_for_date[] = $time_val;
                    }
                }
                if (!empty($valid_slots_for_date)) {
                     sort($valid_slots_for_date); // Sort times
                    $final_json_to_store_array[$date_key] = array_unique($valid_slots_for_date); // Ensure unique
                }
            }
        }
        ksort($final_json_to_store_array); // Sort dates

        $json_to_store_string = json_encode($final_json_to_store_array);

        $update_sql = "UPDATE doctors SET available_slots_json = ? WHERE id = ?";
        $stmt_update = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt_update, "si", $json_to_store_string, $doctor_id);
        if (mysqli_stmt_execute($stmt_update)) {
            set_message("Availability updated successfully!", "success");
            $current_availability_json_for_js = $json_to_store_string; // Reflect change immediately
        } else {
            set_message("Error updating availability: " . mysqli_stmt_error($stmt_update), "danger");
        }
        mysqli_stmt_close($stmt_update);
    } else {
         set_message("Invalid availability data submitted (JSON parse error). Please try again.", "danger");
    }
}
// For textarea display, we want pretty print if possible
$current_availability_pretty_for_textarea = json_encode(json_decode($current_availability_json_for_js), JSON_PRETTY_PRINT);

?>

<h1><i class="bi bi-calendar-plus"></i> Manage Your Availability</h1>
<p class="lead">Select a date, then add or remove available time slots for that day. Your changes are compiled and saved for the entire schedule.</p>
<hr>

<div class="card shadow-sm">
    <div class="card-body">
        <form id="availabilityForm" method="POST" action="<?php echo $base_url; ?>/doctor/manage_availability.php">
            <div class="row align-items-end">
                <div class="col-md-4 mb-3">
                    <label for="selected_date_picker" class="form-label fw-bold">Select Date to Manage:</label>
                    <input type="date" class="form-control form-control-lg" id="selected_date_picker" min="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>

            <div id="slots_management_area" class="mt-3 p-3 border rounded bg-light" style="display: none;">
                <h4 id="editing_date_header" class="mb-3">Editing slots for: <span class="text-primary"></span></h4>
                <div class="row">
                    <div class="col-md-7">
                        <h5><i class="bi bi-list-task"></i> Current Slots for <span class="current-date-display text-primary"></span>:</h5>
                        <ul id="slots_list_for_selected_date" class="list-group mb-3" style="max-height: 250px; overflow-y: auto;">
                            <li class="list-group-item no-slots fst-italic text-muted" style="display: none;">No slots defined for this date.</li>
                        </ul>
                    </div>
                    <div class="col-md-5">
                        <h5><i class="bi bi-plus-circle-dotted"></i> Add New Slot:</h5>
                        <div class="input-group mb-2">
                            <input type="time" class="form-control" id="new_time_slot_input" step="1800">
                            <button class="btn btn-success" type="button" id="add_slot_button"><i class="bi bi-plus-lg"></i> Add</button>
                        </div>
                        <small class="form-text text-muted">Time will be added in HH:MM format.</small>
                         <button class="btn btn-outline-danger btn-sm mt-3 w-100" type="button" id="clear_slots_for_date_button"><i class="bi bi-trash2-fill"></i> Clear All Slots for This Date</button>
                    </div>
                </div>
            </div>
            
            <input type="hidden" name="full_availability_json" id="full_availability_json_input">
            
            <hr class="my-4">
            <button type="submit" class="btn btn-primary btn-lg w-100" id="save_all_changes_button"><i class="bi bi-save-fill"></i> Save All Availability Changes</button>
        </form>
    </div>
</div>

<div class="mt-4">
    <h4><i class="bi bi-card-checklist"></i> Current Full Schedule Overview (Data to be saved)</h4>
    <pre id="current_schedule_overview" class="bg-dark text-light p-3 border rounded" style="max-height: 300px; overflow-y: auto; white-space: pre-wrap;"></pre>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const datePicker = document.getElementById('selected_date_picker');
    const slotsManagementArea = document.getElementById('slots_management_area');
    const editingDateHeaderSpan = document.getElementById('editing_date_header').querySelector('span');
    const currentDateDisplaySpans = document.querySelectorAll('.current-date-display');
    const slotsListUl = document.getElementById('slots_list_for_selected_date');
    const noSlotsLiTemplate = slotsListUl.querySelector('.no-slots').cloneNode(true); // Clone for reuse
    slotsListUl.querySelector('.no-slots').remove(); // Remove initial template from live DOM
    
    const newTimeInput = document.getElementById('new_time_slot_input');
    const addSlotButton = document.getElementById('add_slot_button');
    const clearSlotsForDateButton = document.getElementById('clear_slots_for_date_button');

    const hiddenJsonInput = document.getElementById('full_availability_json_input');
    const scheduleOverviewPre = document.getElementById('current_schedule_overview');

    let doctorAvailabilityData = {}; // Main data store: {"YYYY-MM-DD": ["HH:MM", "HH:MM"], ...}

    function initializeAvailability() {
        try {
            // The PHP var $current_availability_json_for_js is already a JSON string
            const initialJsonString = <?php echo $current_availability_json_for_js; ?>;
            // We need to parse this string into a JS object
            const parsedData = JSON.parse(initialJsonString); 

            if (typeof parsedData === 'object' && parsedData !== null) {
                doctorAvailabilityData = parsedData; // Assign directly
            } else {
                doctorAvailabilityData = {};
            }
        } catch (e) {
            console.error("Error parsing initial availability JSON:", e);
            console.log("Raw JSON string from PHP:", <?php echo json_encode($current_availability_json_for_js); ?>);
            doctorAvailabilityData = {};
        }
        updateScheduleOverview(); // Update the <pre> tag
    }

    function formatDateForDisplay(dateString) { // YYYY-MM-DD
        if (!dateString) return "";
        const dateObj = new Date(dateString + 'T00:00:00Z'); // Use 'Z' for UTC to avoid timezone offset issues in display
        return dateObj.toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', timeZone: 'UTC' });
    }
    
    function formatTimeForDisplay(timeString) { // HH:MM
        if (!timeString) return "";
        const [hours, minutes] = timeString.split(':');
        const date = new Date(); // Temporary date object for formatting
        date.setUTCHours(parseInt(hours, 10));
        date.setUTCMinutes(parseInt(minutes, 10));
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true, timeZone: 'UTC' });
    }

    function renderSlotsForDate(selectedDate) { // selectedDate is YYYY-MM-DD
        slotsListUl.innerHTML = ''; // Clear existing slots

        const slotsForThisDate = doctorAvailabilityData[selectedDate] || [];

        if (slotsForThisDate.length === 0) {
            const currentNoSlotsLi = noSlotsLiTemplate.cloneNode(true);
            currentNoSlotsLi.style.display = 'block';
            slotsListUl.appendChild(currentNoSlotsLi);
        } else {
            // doctorAvailabilityData[selectedDate] should already be sorted if addSlot sorts it
            slotsForThisDate.forEach(timeSlot => { // timeSlot is HH:MM
                const listItem = document.createElement('li');
                listItem.className = 'list-group-item d-flex justify-content-between align-items-center py-2';
                
                const timeDisplaySpan = document.createElement('span');
                timeDisplaySpan.textContent = formatTimeForDisplay(timeSlot);
                listItem.appendChild(timeDisplaySpan);

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'btn btn-outline-danger btn-sm';
                removeButton.innerHTML = '<i class="bi bi-trash"></i>';
                removeButton.dataset.time = timeSlot; // Store time for removal
                removeButton.onclick = function() {
                    removeSlot(selectedDate, this.dataset.time);
                };
                listItem.appendChild(removeButton);
                slotsListUl.appendChild(listItem);
            });
        }
        updateFullJsonForForm(); // Update hidden input whenever UI changes for a date
    }

    function addSlot(date, time) { // date YYYY-MM-DD, time HH:MM
        if (!date) {
            alert("Please select a date first.");
            return;
        }
        if (!time) {
            alert("Please enter a time using the time picker.");
            return;
        }
        
        if (!doctorAvailabilityData[date]) {
            doctorAvailabilityData[date] = [];
        }

        if (doctorAvailabilityData[date].includes(time)) {
            alert("This time slot (" + formatTimeForDisplay(time) + ") is already added for " + formatDateForDisplay(date) + ".");
            return;
        }

        doctorAvailabilityData[date].push(time);
        doctorAvailabilityData[date].sort((a, b) => a.localeCompare(b)); // Sort HH:MM strings
        renderSlotsForDate(date);
        newTimeInput.value = ''; // Clear input
    }

    function removeSlot(date, time) {
        if (doctorAvailabilityData[date]) {
            doctorAvailabilityData[date] = doctorAvailabilityData[date].filter(slot => slot !== time);
            if (doctorAvailabilityData[date].length === 0) {
                // If all slots are removed for a date, remove the date key itself
                delete doctorAvailabilityData[date]; 
            }
            renderSlotsForDate(date); // Re-render the list for the current date
        }
    }
    
    function clearAllSlotsForDate(date) {
        if (!date) {
            alert("Please select a date to clear.");
            return;
        }
        if (doctorAvailabilityData[date] && doctorAvailabilityData[date].length > 0) {
            if (confirm('Are you sure you want to remove all slots for ' + formatDateForDisplay(date) + '?')) {
                delete doctorAvailabilityData[date]; // Remove the date entry entirely
                renderSlotsForDate(date); 
            }
        } else {
            alert('No slots to clear for ' + formatDateForDisplay(date) + '.');
        }
    }

    // This function updates the PRE tag for visual feedback
    function updateScheduleOverview() {
        const sortedDates = Object.keys(doctorAvailabilityData).sort();
        const overviewObject = {};
        let hasAnyData = false;
        sortedDates.forEach(date => {
            if (doctorAvailabilityData[date] && doctorAvailabilityData[date].length > 0) {
                 // Ensure times within each date are sorted for the overview
                overviewObject[date] = [...doctorAvailabilityData[date]].sort((a,b) => a.localeCompare(b));
                hasAnyData = true;
            }
        });
        scheduleOverviewPre.textContent = hasAnyData ? JSON.stringify(overviewObject, null, 2) : "No availability scheduled yet. Select a date and add slots.";
    }

    // This function updates the hidden form input with the complete JSON
    function updateFullJsonForForm() {
        const finalAvailabilityData = {};
        const sortedDates = Object.keys(doctorAvailabilityData).sort();
        sortedDates.forEach(date => {
            if (doctorAvailabilityData[date] && doctorAvailabilityData[date].length > 0) {
                // Ensure times are sorted before stringifying for the form
                finalAvailabilityData[date] = [...doctorAvailabilityData[date]].sort((a,b) => a.localeCompare(b));
            }
        });
        hiddenJsonInput.value = JSON.stringify(finalAvailabilityData);
        updateScheduleOverview(); // Also update the visual <pre> tag
    }


    datePicker.addEventListener('change', function() {
        const selectedValue = this.value; // YYYY-MM-DD
        if (selectedValue) {
            slotsManagementArea.style.display = 'block';
            const formattedDisplayDate = formatDateForDisplay(selectedValue);
            editingDateHeaderSpan.textContent = formattedDisplayDate;
            currentDateDisplaySpans.forEach(span => span.textContent = formattedDisplayDate); // Update other displays if any
            renderSlotsForDate(selectedValue);
        } else {
            slotsManagementArea.style.display = 'none';
        }
    });

    addSlotButton.addEventListener('click', function() {
        const selectedDate = datePicker.value;
        const newTime = newTimeInput.value; // HH:MM from <input type="time">
        addSlot(selectedDate, newTime);
    });
    
    clearSlotsForDateButton.addEventListener('click', function() {
        const selectedDate = datePicker.value;
        clearAllSlotsForDate(selectedDate);
    });

    // Populate the hidden input before form submission using the main "Save All" button
    document.getElementById('availabilityForm').addEventListener('submit', function(event) {
        // The updateFullJsonForForm() is called after every modification,
        // so the hiddenJsonInput should already be up-to-date.
        // We can call it one last time just to be absolutely sure.
        updateFullJsonForForm();
        console.log("Submitting JSON:", hiddenJsonInput.value); // For debugging
    });

    // Initialize
    initializeAvailability();
});
</script>

<?php mysqli_close($conn); ?>
<?php require_once 'partials/doctor_footer.php'; ?>