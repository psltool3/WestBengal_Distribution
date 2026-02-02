<?php
require('util/Connection.php');
require('util/SessionCheck.php');
require('Header.php');
?>
<script src="crypto-js/crypto-js.js"></script>
<script src="js/Encryption.js"></script>

<script>
    function verifyCaptcha() {
        var readableString = document.getElementById("password").value;
        var nonceValue = "nonce_value";
        let encryption = new Encryption();
        var encrypted = encryption.encrypt(readableString, nonceValue);
        document.getElementById("password").value = encrypted;
    }
</script>
<!-- START BREADCRUMB -->
<ul class="breadcrumb">
    <li><a href="WholeSale.php">Home</a></li>
    <li class="active">WholeSale Add</li>
</ul>
<!-- END BREADCRUMB -->


<!-- PAGE CONTENT WRAPPER -->
<div class="page-content-wrap">

    <div class="row">
        <div class="col-md-12">

            <form action="api/WholeSaleAdd.php" method="POST" class="form-horizontal" enctype="multipart/form-data">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <p>Fill this form to add new WholeSale.</p>
                    </div>

                    <div class="panel-body">

                        <div class="row">

                            <div class="col-md-6">

                                <div class="form-group">
                                    <label class="col-md-3 control-label">Name of WholeSale*</label>
                                    <div class="col-md-9">
                                        <div class="input-group">
                                            <span class="input-group-addon"><span class="fa fa-info"></span></span>
                                            <input type="text" class="form-control" id="name" name="name" required />
                                        </div>
                                        <span class="help-block">WholeSale Name</span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-md-3 control-label">Type*</label>
                                    <div class="col-md-9">
                                        <div class="input-group">
                                            <span class="input-group-addon"><span class="fa fa-info"></span></span>
                                            <input type="text" class="form-control" id="type" name="type" required />
                                        </div>
                                        <span class="help-block">Type</span>
                                    </div>
                                </div>



                                <div class="form-group">
                                    <label class="col-md-3 control-label">Latitude of WholeSale*</label>
                                    <div class="col-md-9">
                                        <div class="input-group">
                                            <span class="input-group-addon"><span class="fa fa-info"></span></span>
                                            <input type="text" class="form-control" id="latitude" name="latitude"
                                                required />
                                        </div>
                                        <span class="help-block">Latitude of WholeSale</span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-md-3 control-label">Longitude of WholeSale*</label>
                                    <div class="col-md-9">
                                        <div class="input-group">
                                            <span class="input-group-addon"><span class="fa fa-info"></span></span>
                                            <input type="text" class="form-control" id="longitude" name="longitude"
                                                required />
                                        </div>
                                        <span class="help-block">Longitude of WholeSale</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">

                                <div class="form-group">
                                    <label class="col-md-3 control-label">District*</label>
                                    <div class="col-md-9">
                                        <div class="input-group">
                                            <span class="input-group-addon"><span
                                                    class="fa fa-arrow-down"></span></span>
                                            <select class="form-control" id="district" name="district">
                                            </select>
                                        </div>
                                        <span class="help-block">District</span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-md-3 control-label">WholeSale Id*</label>
                                    <div class="col-md-9">
                                        <div class="input-group">
                                            <span class="input-group-addon"><span class="fa fa-info"></span></span>
                                            <input type="text" class="form-control" id="id" name="id" required />
                                        </div>
                                        <span class="help-block">WholeSale ID</span>
                                    </div>
                                </div>



                                <div class="form-group">
                                    <label class="col-md-3 control-label">Storage in Quintals*</label>
                                    <div class="col-md-9">
                                        <div class="input-group">
                                            <span class="input-group-addon"><span class="fa fa-info"></span></span>
                                            <input type="text" class="form-control" id="storage" name="storage"
                                                required />
                                        </div>
                                        <span class="help-block">Storage in Quintals</span>
                                    </div>
                                </div>

                            </div>

                        </div>

                    </div>
                    <div class="panel-footer">
                        <button class="btn btn-primary pull-right" onclick="showPopup()" type="button">Submit</button>
                    </div>
                    <div id="popup" class="popup">
                        <a class="close" onclick="hidePopup()" style="font-size:25px">×</a>
                        </br></br>

                        <div class="col-md-6">

                            <div class="form-group">
                                <label class="col-md-3 control-label">Username*</label>
                                <div class="col-md-9">
                                    <div class="input-group">
                                        <span class="input-group-addon"><span class="fa fa-info"></span></span>
                                        <input type="text" class="form-control" id="username" name="username"
                                            required />
                                    </div>
                                    <span class="help-block">Username</span>
                                </div>
                            </div>


                        </div>
                        <div class="col-md-6">


                            <div class="form-group">
                                <label class="col-md-3 control-label">Password*</label>
                                <div class="col-md-9">
                                    <div class="input-group">
                                        <span class="input-group-addon"><span class="fa fa-info"></span></span>
                                        <input type="password" class="form-control" id="password" name="password"
                                            required />
                                    </div>
                                    <span class="help-block">Password</span>
                                </div>
                            </div>


                        </div>

                        <center><button class="btn btn-primary" onclick="verifyCaptcha()">Verify</button></center>
                    </div>
                </div>
            </form>

        </div>
    </div>
    </br></br></br></br></br></br></br></br></br></br></br></br></br></br></br></br></br></br></br>
</div>
</div>
<!-- END SIMPLE DATATABLE -->

</div>
</div>

</div>
<!-- PAGE CONTENT WRAPPER -->
</div>
<!-- END PAGE CONTENT -->
</div>
<!-- END PAGE CONTAINER -->



<!-- START SCRIPTS -->
<script type="text/javascript" src="js/plugins/jquery/jquery.min.js"></script>
<script type="text/javascript" src="js/plugins/jquery/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/plugins/bootstrap/bootstrap.min.js"></script>
<!-- END PLUGINS -->

<!-- THIS PAGE PLUGINS -->
<script type='text/javascript' src='js/plugins/icheck/icheck.min.js'></script>
<script type="text/javascript" src="js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>
<script type="text/javascript" src="js/plugins/datatables/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="js/plugins/tableexport/tableExport.js"></script>
<script type="text/javascript" src="js/plugins/tableexport/jquery.base64.js"></script>
<script type="text/javascript" src="js/plugins/tableexport/html2canvas.js"></script>
<script type="text/javascript" src="js/plugins/tableexport/jspdf/libs/sprintf.js"></script>
<script type="text/javascript" src="js/plugins/tableexport/jspdf/jspdf.js"></script>
<script type="text/javascript" src="js/plugins/tableexport/jspdf/libs/base64.js"></script>


<script type="text/javascript" src="js/plugins.js"></script>
<script type="text/javascript" src="js/actions.js"></script>
<!-- END PAGE PLUGINS -->
<?php require('DistrictAutocomplete.php'); ?>
<script>
    function showPopup() {

        var name = document.getElementById('name').value;
        var type = document.getElementById('type').value;
        var latitude = document.getElementById('latitude').value;
        var longitude = document.getElementById('longitude').value;
        var id = document.getElementById('id').value;
        var storage = document.getElementById('storage').value;
        var district = document.getElementById('district').value;

        if (name === '' || type === '' || latitude === '' || longitude === '' || id === '' || storage === '' || district === '') {
            alert('Please enter all fields');
            return false;
        }

        document.getElementById('popup').style.display = 'block';
    }

    function hidePopup() {
        document.getElementById('popup').style.display = 'none';
    }

</script>

</body>

</html>