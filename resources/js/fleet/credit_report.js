document.getElementById('download-report').addEventListener('click', () => {
    const routeId = document.getElementById('routeSelect').value;
    // Redirect browser to your CSV route with selected route_id
    window.location.href = `/fleet-credit-report/csv?route_id=${routeId}`;
});
