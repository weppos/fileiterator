<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * FileIterator - Iterator implementation for reading and parsing files
 *
 * Copyright (c) 2007 Simone Carletti
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * If you have any questions or comments, please email:
 * Simone Carletti
 * weppos@weppos.net
 * http://www.simonecarletti.com/
 *
 *
 * PHP versions 5
 *
 * @category    CsvIterator
 * @package     CsvIterator
 * @author      Simone Carletti <weppos@weppos.net>
 * @copyright   2007 Simone Carletti
 * @license     http://creativecommons.org/licenses/LGPL/2.1/ LGPL License 2.1
 * @version     SVN: $Id:CsvIterator.php 3 2007-02-17 22:09:53Z weppos $
 * @link
 */


/**
 * CsvIterator is an implementation of Iterator interface
 * for reading and parsing Comma Separated Value files.
 *
 * @category    CsvIterator
 * @package     CsvIterator
 * @author      Simone Carletti <weppos@weppos.net>
 * @copyright   2007 Simone Carletti
 * @license     http://creativecommons.org/licenses/LGPL/2.1/ LGPL License 2.1
 * @link
 */
class FileIterator implements Iterator
{
    /** Name */
    const NAME = 'FileIterator';
    /** Author */
    const AUTHOR = 'Simone Carletti <weppos@weppos.net>';
    /** Version */
    const VERSION = '0.1.0';
    /** Status */
    const STATUS = 'alpha';
    /** Build */
    const BUILD = '$Rev: 7 $';

    /** SVN ID */
    const SVN_ID = '$Id:CsvIterator.php 3 2007-02-17 22:09:53Z weppos $';
    /** SVN Revision */
    const SVN_REVISION = '$Rev: 7 $';
    /** SVN Date  */
    const SVN_BUILD = '$Date: 2007-06-14 17:21:31 +0200 (Thu, 14 Jun 2007) $';

    /** File row size */
    const ROW_SIZE = 4096;


    /**
     * Verbose output
     *
     * @var     bool
     * @access  private
     */
    private $_verbose = false;

    /**
     * File pointer.
     *
     * @var     resource
     * @access  private
     */
    private $_filePointer;

    /**
     * Path to source file
     *
     * @var     string
     * @access  private
     */
    private $_source;

    /**
     * File data.
     *
     * @var     array
     * @access  private
     */
    private $_fileData;

    /**
     * Current element, which will be returned on each iteration.
     * Substantially, it's represented by a file row.
     *
     * @var     array
     * @access  private
     */
    private $_currentElement = null;

    /**
     * Current line of file.
     *
     * @var     int
     * @access  private
     */
    private $_currentIndex;

    /**
     * Whether current element is valid or not.
     *
     * @var     bool
     * @access  private
     */
    private $_valid;


    /**
     * Class constructor
     */
    public function __construct($source)
    {
        $this->_source = $source;
        // constructor should initialize
        // first element and first index
        $this->_rewindPointer();
    }


    /**
     * Return the current element
     *
     * Similar to the current() function for arrays.
     * Implement Iterator::current()
     *
     * @return  string
     * @see     Iterator::current()
     */
    public function current()
    {
        return $this->_currentElement;
    }

    /**
     * Return the identifying key of the current element
     *
     * Similar to the key() function for arrays.
     * Implement Iterator::key()
     *
     * @return  int
     * @see     Iterator::key()
     */
    public function key()
    {
        return $this->_currentIndex;
    }

    /**
     * Move forward to next element
     *
     * Similar to the next() function for arrays.
     * Implement Iterator::next()
     *
     * @return  void
     * @see     Iterator::next()
     */
    public function next()
    {
        if (!is_resource($this->_filePointer))
            throw new Exception('Invalid file handler. ' .
                'Either you reached the end of file or the stream is invalid. ' .
                'Use rewind() to return to first element or valid() to check whether the stream is valid');

        if (!feof($this->_filePointer)) {
            $this->_currentElement = fgets( $this->_filePointer,
                                            self::ROW_SIZE);
            $this->_currentIndex++;
            $this->_valid = true;
        }
        else {
			if ($this->_verbose) echo "File pointer reached the end of the file\n";

			if ($this->_verbose) echo sprintf("Closing stream connection to '%s'... ", $this->_source);
            fclose($this->_filePointer);
			if ($this->_verbose) echo "done!\n";

            $this->_valid = false;
        }

        /* next() shuld retur void
        return $this->_valid; */
    }

    /**
     * Rewind the Iterator to the first element
     *
     * Similar to the reset() function for arrays.
     * Implement Iterator::rewind()
     *
     * @return  void
     * @see     Iterator::rewind()
     */
    public function rewind()
    {
        $this->_rewindPointer($this->_source);
    }

    /**
     * Check if there is a current element after calls to
     * rewind() or next().
     * Used to check if we've iterated to the end of the collection.
     *
     * This method checks if the next row is a valid row.
     *
     * @return   bool    FALSE if there's nothing more to iterate over.
     */
    public function valid()
    {
        return $this->_valid;
    }


    /**
     * Checks whether current file row is empty
     *
     * @param   bool $whitespaceAsChar
     * @return  bool
     */
    public function isEmpty($whitespaceAsChar = true)
    {
        $line = $this->current();
        if (!$whitespaceAsChar) $line = trim($line);
        return strlen($line) == 0;
    }

    /**
     * Checks whether current file row matches given regexp pattern
     *
     * If $matches is provided, then it is filled with the results of search.
     *
     * @param   string  $pattern
     * @param   string  $matches
     * @return  bool
     */
    public function match($pattern, &$matches = null)
    {
		return preg_match($pattern, $this->current(), $matches);
    }


    /**
     * Opens and returns file pointer to given file
     *
     * @param   string      $source
     * @return  resource
     * @access  private
     */
    private function _openPointer($source = null)
    {
        if ($source !== null) $this->_source = $source;

        try {
			if ($this->_verbose) echo sprintf("Opening stream connection to '%s'... ", $this->_source);
            $this->_filePointer = fopen($this->_source, 'r');
        }
        catch (Exception $e) {
			if ($this->_verbose) echo "failed!\n";
            throw new Exception(sprintf("File '%s' cannot be read.", $this->_source));
        }

		if ($this->_verbose) echo "done!\n";
        return $this->_filePointer;
    }

    /**
     * Rewind file pointer to the beginning and
     * reset all loop variables
     *
     * @return  void
     * @access  private
     */
    private function _rewindPointer()
    {
        if (!is_resource($this->_filePointer)) {
			if ($this->_verbose) echo "Stream connection closed, a stream connection request is going to be sent.\n";
            $this->_openPointer();
		}

        rewind($this->_filePointer);
		if ($this->_verbose) echo "File pointer set to the beginning of the file\n";

        $this->_currentIndex = -1;
        $this->next();
    }
}


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
