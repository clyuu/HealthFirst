from test_01_patient_login_and_qr import run as run_patient_login_and_qr
from test_02_public_emergency_report import run as run_public_emergency_report
from test_03_hospital_ambulance_assignment import run as run_hospital_assignment
from test_04_ambulance_pickup_arrival import run as run_ambulance_pickup_arrival
from test_05_paramedic_injury_report import run as run_paramedic_injury_report
from test_06_doctor_admission_discharge import run as run_doctor_admission_discharge
from test_07_admin_setup_workflow import run as run_admin_setup_workflow
from test_08_role_access_security import run as run_role_access_security


def test_AT001_patient_login_and_qr_access():
    run_patient_login_and_qr()


def test_AT002_public_emergency_report_workflow():
    run_public_emergency_report()


def test_AT003_hospital_ambulance_assignment_workflow():
    run_hospital_assignment()


def test_AT004_ambulance_pickup_and_arrival_workflow():
    run_ambulance_pickup_arrival()


def test_AT005_paramedic_injury_report_workflow():
    run_paramedic_injury_report()


def test_AT006_doctor_admission_and_discharge_workflow():
    run_doctor_admission_discharge()


def test_AT007_admin_setup_workflow():
    run_admin_setup_workflow()


def test_AT008_role_based_access_security():
    run_role_access_security()
