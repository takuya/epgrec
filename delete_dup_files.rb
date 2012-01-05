#!/usr/bin/env ruby

require './model.rb'


#title,descriptionが同じ番組が録画されていたら、
#再放送とみなして1つをのこして削除する。
#
Reserve.delete_duplicated_rec



puts "END"

exit
