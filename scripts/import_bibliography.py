import sqlite3
import config

bibliography_location = '../tex/my.bib'

def clear_bibliography():
  try:
    query = 'DELETE FROM bibliography_items'
    connection.execute(query)

    query = 'DELETE FROM bibliography_values'
    connection.execute(query)

  except sqlite3.Error, e:
    print "An error occurred:", e.args[0]

def insert_item(item):
  try:
    query = 'INSERT INTO bibliography_items (name, type) VALUES (?, ?)'
    connection.execute(query, (item[0][1], item[0][0]))

    for (key, value) in item[1].iteritems():
      query = 'INSERT INTO bibliography_values (name, key, value) VALUES (?, ?, ?)'
      connection.execute(query, (item[0][1], key, value))

  except sqlite3.Error, e:
    print "An error occurred:", e.args[0]

def import_bibliography(location):
  f = open(location)

  items = []
  not_finished = False

  for line in f:
    # beginning of a new item
    if line[0] == '@':
      # clear previous item
      item = [[], {}]

      bib_type = line.partition('{')[0].strip('@').lower()
      name = line.partition('{')[2].strip().strip(',')

      item[0] = (bib_type, name)

      continue
  
    # end of an item
    if line[0] == '}':
      # add a *copy* to the list of items
      items.append(list(item))

      continue
 
    # check whether we're still building a value or not
    if not_finished:
      # append to current value
      value = value + ' ' + line.strip().strip(',')
      # now it's finished
      if line.strip()[-2:] == '},' or line.strip()[-2:] == '",' or line.strip()[-1] == '}' or line.strip()[-1:] == '"':
        not_finished = False
        item[1][key] = value

    else:
      if '=' in line:
        key = line.partition('=')[0].strip().lower()

        if line.strip()[-2:] == '},' or line.strip()[-2:] == '",' or line.strip()[-1] == '}' or line.strip()[-1:] == '"':
          value = line.partition('=')[2].strip().strip(',')[1:-1]
          item[1][key] = value

        else:
          not_finished = True
          value = line.partition('=')[2].strip()
  

  for item in items:
    insert_item(item)

connection = sqlite3.connect(config.database)

print 'Clearing bibliography'
clear_bibliography()
print 'Importing bibliography'
import_bibliography(config.bibliography_file)

connection.commit()
connection.close()

