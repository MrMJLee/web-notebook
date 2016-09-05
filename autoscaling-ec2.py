import boto.ec2.autoscale

from boto.ec2.autoscale import LaunchConfiguration
from boto.ec2.autoscale import AutoScalingGroup
from boto.ec2.autoscale import ScalingPolicy
from boto.ec2.cloudwatch import MetricAlarm
import sys

ACCESS_KEY="<your own AWS access key>"
SECRET_KEY="<your own AWS secret key>"

EC2_KEY_HANDLE = "<keypair name>"

REGION = "us-west-2"
AMI_ID = "<your image>"
INSTANCE_TYPE = "t2.micro"
SECGROUP_HANDLE = "launch-wizard-1"

print("Connecting to Autoscaling Service")

conn = boto.ec2.autoscale.connect_to_region(REGION,
                                            aws_access_key_id=ACCESS_KEY,
                                            aws_secret_access_key=SECRET_KEY)

lc = LaunchConfiguration(name="Launch-Configuration",
                         image_id=AMI_ID,
                         key_name=EC2_KEY_HANDLE,
                         instance_type=INSTANCE_TYPE,
                         security_groups=[SECGROUP_HANDLE, ])

print("\nCreating launch configuration")
try:
    conn.create_launch_configuration(lc)
except:
    print("Oops!", sys.exc_info()[0], "occured.")
    print("The launch configuration may already exist.")
    print("")

ag = AutoScalingGroup(group_name="Analystic-Group",
                      availability_zones=["us-west-2b"],
                      launch_config=lc,
                      min_size=1,   # minimum size of instances as 1
                      max_size=2,   # maximum size of instances as 2
                      connection=conn,
                      load_balancers=['Twitter-Analystic-LB'],
                      heath_check_type='ELB',health_check_period='120'
                      )

print("Creating auto-scaling group")
try:
    conn.create_auto_scaling_group(ag)
    print("")
except:
    print("Oops!", sys.exc_info()[0], "occured.")
    print("The auto-scaling group may already exist.")
    print("")

print("Creating auto-scaling policies")

#create auto-scaling policies
scale_up_policy = ScalingPolicy(name='scale_up',adjustment_type='ChangeInCapacity',
                                as_name="Analystic-Group",scaling_adjustment=1,cooldown=180)
scale_down_policy = ScalingPolicy(name='scale_down',adjustment_type='ChangeInCapacity',
                                   as_name="Analystic-Group",scaling_adjustment=-1,cooldown=180)
conn.create_scaling_policy(scale_up_policy)
conn.create_scaling_policy(scale_down_policy)

scale_up_policy = conn.get_all_policies(as_group="Analystic-Group", policy_names=["scale_up"])[0]
scale_down_policy = conn.get_all_policies(as_group="Analystic-Group", policy_names=["scale_down"])[0]

print "Connecting to CloudWatch"

cloudwatch = boto.ec2.cloudwatch.connect_to_region(REGION,aws_access_key_id=ACCESS_KEY,aws_secret_access_key=SECRET_KEY)

alarm_dimensions = {"AutoScalingGroupName": "Analystic-Group", }

print("Creating scale-up alarm")

scale_up_alarm = MetricAlarm(name="scale_up_on_cpu",
                             namespace="AWS/EC2",
                             metric="CPUUtilization",
                             statistic="Average",
                             comparison=">",
                             threshold=70,
                             period=60,
                             evaluation_periods=2,
                             alarm_actions=[scale_up_policy.policy_arn],
                             dimensions=alarm_dimensions
                             )
cloudwatch.create_alarm(scale_up_alarm)

print("Creating scale-down alarm")

scale_down_alarm = MetricAlarm(name="scale_down_on_cpu",
                               namespace="AWS/EC2",
                               metric="CPUUtilization",
                               statistic="Average",
                               comparison="<",
                               threshold=50,
                               period=60,
                               evaluation_periods=2,
                               alarm_actions=[scale_down_policy.policy_arn],
                               dimensions=alarm_dimensions
                               )

cloudwatch.create_alarm(scale_down_alarm)

print("Done!")
