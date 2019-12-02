#!/usr/bin/env python

import sys, paramiko, getpass, time

if len(sys.argv) < 2:
    print "args missing"
    print sys.argv[0], " host"
    sys.exit(1)

hostname = sys.argv[1]
port = 22
username = "ubnt"

password = getpass.getpass()
#print 'You entered:', password

pre_commands="""enable
password
show network
configure
"""

poe_comms="""interface 0/$int
poe opmode shutdown
exit"""

#poe opmode auto
#poe opmode shutdown

if len(sys.argv) > 2 and sys.argv[2]=='up':
    print "turning poe auto"
    poe_comms=poe_comms.replace('shutdown','auto')
    

post_commands="""exit
exit
"""

try:
    client = paramiko.SSHClient()
    #client.load_system_host_keys()
    #client.set_missing_host_key_policy(paramiko.WarningPolicy())
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())

    client.connect(hostname, port=port, username=username, password=password)
    conn=client.invoke_shell()
    
    pre_commands=pre_commands.replace('password',password)
    
    for com in pre_commands.split('\n'):
        conn.send(com + '\n')
        time.sleep(1)
        output = conn.recv(buffer)
        print output
        
	#################
    for i in range(1,24): 
        #print "i=", i
        commands=poe_comms.replace('$int', str(i))
        for com in commands.split('\n'):
            conn.send(com + '\n')
            time.sleep(1)
            output = conn.recv(buffer)
            print output
            
    #################        
    for com in post_commands.split('\n'):
        conn.send(com + '\n')
        time.sleep(1)
        output = conn.recv(buffer)
        print output

finally:
    client.close()
