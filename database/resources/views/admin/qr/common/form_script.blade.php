<script>
    $(document).ready(function () {
        const loadDepartments = async () => {
            const selectedBranchId = $('#branch_id').val();
            if (!selectedBranchId) return;

            try {
                $('#department_id').empty().append('<option selected disabled>{{ __("index.select_department") }}</option>');
                const response = await $.ajax({
                    type: 'GET',
                    url: `{{ url('admin/departments/get-All-Departments') }}/${selectedBranchId}`,
                });

                if (!response || !response.data || response.data.length === 0) {
                    $('#department_id').append('<option disabled>{{ __("index.no_departments_found") }}</option>');
                    return;
                }

                response.data.forEach(data => {
                    $('#department_id').append(`<option value="${data.id}">${data.dept_name}</option>`);
                });
            } catch (error) {
                console.error('Error loading departments:', error);
                $('#department_id').append('<option disabled>{{ __("index.error_loading_departments") }}</option>');
            }
        };

        // Load departments when branch is selected
        $('#branch_id').change(loadDepartments);


    });
</script>
