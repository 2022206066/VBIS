// Toastr options
if (typeof toastr !== 'undefined') {
  toastr.options = {
    "closeButton": true,
    "debug": false,
    "newestOnTop": true,
    "progressBar": true,
    "positionClass": "toast-top-right",
    "preventDuplicates": false,
    "onclick": null,
    "showDuration": "300",
    "hideDuration": "1000",
    "timeOut": "5000",
    "extendedTimeOut": "1000",
    "showEasing": "swing",
    "hideEasing": "linear",
    "showMethod": "fadeIn",
    "hideMethod": "fadeOut"
  };
} else {
  // Create dummy toastr if not available
  window.toastr = {
    options: {},
    success: function(msg) { console.log('Success:', msg); },
    error: function(msg) { console.log('Error:', msg); },
    warning: function(msg) { console.log('Warning:', msg); },
    info: function(msg) { console.log('Info:', msg); }
  };
}
